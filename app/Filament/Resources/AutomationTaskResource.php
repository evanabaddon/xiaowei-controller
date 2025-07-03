<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Device;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\AutomationTask;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use App\Jobs\DispatchAutomationToDevice;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AutomationTaskResource\Pages;
use App\Filament\Resources\AutomationTaskResource\RelationManagers;

class AutomationTaskResource extends Resource
{
    protected static ?string $model = AutomationTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Konfigurasi Eksekusi')
                ->schema([
                    Toggle::make('apply_to_all')
                        ->label('Jalankan ke semua device?')
                        ->live(),
        
                    Select::make('device_id')
                        ->label('Pilih device')
                        ->options(
                            fn () => \App\Models\Device::all()->mapWithKeys(fn ($device) => [
                                $device->id => "{$device->android_id} - {$device->model} - {$device->machine?->name}"
                            ])
                        )
                        ->searchable()
                        ->visible(fn (Get $get) => !$get('apply_to_all'))
                        ->required(fn (Get $get) => !$get('apply_to_all')),
        
                    Select::make('mode')
                        ->label('Mode Eksekusi')
                        ->options([
                            'manual' => 'Manual (dijalankan lewat tombol)',
                            'otomatis' => 'Otomatis (dipantau AutoX)',
                        ])
                        ->default('otomatis')
                        ->required(),
                ])
                ->columns(1),
        
            Section::make('Langkah-langkah Otomasi')
                ->description('Urutan aksi yang akan dijalankan di perangkat Android')
                ->schema([
                    Repeater::make('steps')
                        ->label('Langkah-langkah')
                        ->schema([
                            Select::make('action')
                                ->label('Aksi')
                                ->options([
                                    'toast' => 'Tampilkan Toast',
                                    'sleep' => 'Tunggu',
                                    'tap' => 'Tap (x,y)',
                                    'clickText' => 'Klik Teks',
                                    'swipe' => 'Geser (Swipe)',
                                    'launchApp' => 'Buka Aplikasi',
                                    'input' => 'Isi Teks',
                                ])
                                ->required()
                                ->live(),

                            TextInput::make('text')
                                ->label('Teks')
                                ->visible(fn ($get) => in_array($get('action'), ['toast', 'clickText', 'input']))
                                ->nullable(),

                            Group::make([
                                    Select::make('app')
                                        ->label('Pilih Aplikasi (Opsional)')
                                        ->visible(fn ($get) => $get('action') === 'launchApp')
                                        ->options(function (Get $get) {
                                            $deviceId = request()->input('data.device_id') ?? request()->input('device_id');
                                            if (!$deviceId) return [];
                                            $device = \App\Models\Device::find($deviceId);
                                            if (!$device) return [];
                                
                                            $apps = $device->getInstalledApps();
                                            return collect($apps)->mapWithKeys(fn ($app) => [$app => $app]);
                                        })
                                        ->searchable()
                                        ->nullable(),
                                
                                    TextInput::make('app')
                                        ->label('Atau ketik nama app (package name)')
                                        ->placeholder('com.instagram.android')
                                        ->visible(fn ($get) => $get('action') === 'launchApp')
                                        ->nullable(),
                                ])
                                ->visible(fn ($get) => $get('action') === 'launchApp'),
                                

                            TextInput::make('ms')
                                ->label('Waktu Tunggu (ms)')
                                ->visible(fn ($get) => $get('action') === 'sleep')
                                ->numeric()
                                ->nullable(),

                            TextInput::make('x')
                                ->label('X')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'tap'),

                            TextInput::make('y')
                                ->label('Y')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'tap'),

                            TextInput::make('x1')
                                ->label('X1')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'swipe'),

                            TextInput::make('y1')
                                ->label('Y1')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'swipe'),

                            TextInput::make('x2')
                                ->label('X2')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'swipe'),

                            TextInput::make('y2')
                                ->label('Y2')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'swipe'),

                            TextInput::make('duration')
                                ->label('Durasi Swipe (ms)')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => $get('action') === 'swipe'),
                        ])
                        ->columns(2)
                        ->default([])
                        ->required(),

                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device.name')->label('Device'),
                Tables\Columns\IconColumn::make('apply_to_all')->label('All')->boolean(),

                Tables\Columns\TextColumn::make('device.name')->label('Device')->sortable(),
                Tables\Columns\IconColumn::make('apply_to_all')
                    ->boolean()->label('Semua Device'),
                Tables\Columns\TextColumn::make('mode')->badge(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('jalankan')
                    ->label('Start Sekarang')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->mode === 'manual')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Kirim task ke device sesuai mode
                        if ($record->apply_to_all) {
                            Device::all()->each(function ($device) use ($record) {
                                DispatchAutomationToDevice::dispatch($record, $device);
                            });
                        } else {
                            if ($record->device) {
                                DispatchAutomationToDevice::dispatch($record, $record->device);
                            }
                        }

                        Notification::make()
                            ->title('✅ Task dikirim ke device')
                            ->body($record->apply_to_all 
                                ? 'Task telah dikirim ke semua device.' 
                                : "Task dikirim ke device: {$record->device->android_id}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomationTasks::route('/'),
            'create' => Pages\CreateAutomationTask::route('/create'),
            'edit' => Pages\EditAutomationTask::route('/{record}/edit'),
        ];
    }
}

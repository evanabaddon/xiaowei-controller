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
use Filament\Forms\Components\Fieldset;
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

    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Automation Tools';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Task Name')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name'),
                        Select::make('platform_id')
                            ->label('Platform')
                            ->relationship('platform', 'name') // otomatis mengambil nama dari relasi
                            ->required()
                    ])
                ]),
            Section::make('Execution Configuration')
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make('apply_to_all')
                            ->label('Run on all devices?')
                            ->inline()
                            ->live(),

                        Select::make('device_id')
                            ->label('Select devices')
                            ->options(
                                fn () => \App\Models\Device::all()->mapWithKeys(fn ($device) => [
                                    $device->id => "{$device->android_id} - {$device->model} - {$device->machine?->name}"
                                ])
                            )
                            ->searchable()
                            ->visible(fn (Get $get) => !$get('apply_to_all'))
                            ->required(fn (Get $get) => !$get('apply_to_all')),

                        Select::make('mode')
                            ->label('Execution Mode')
                            ->options([
                                'manual' => 'Manual (executed via button)',
                                'otomatis' => 'Automatic (Monitored AutoX)',
                            ])
                            ->default('otomatis')
                            ->required(),
                    ])
                ])
                ->columns(1),

            Section::make('Automation Steps')
                ->description('The sequence of actions to be executed on device')
                ->schema([
                    Repeater::make('steps')
                        ->label('Steps')
                        ->schema([
                            // PILIH AKSI UTAMA
                            Select::make('action')
                                ->label('Aksi')
                                ->options([
                                    'toast' => 'Tampilkan Toast',
                                    'sleep' => 'Tunggu',
                                    'tap' => 'Tap (x,y)',
                                    'clickText' => 'Klik Teks',
                                    'swipe' => 'Geser (Swipe)',
                                    'launchApp' => 'Buka Aplikasi',
                                    'input' => 'Isi Teks (Manual)',
                                    'uploadImage' => 'Upload Gambar (Generated)',
                                    'inputCaption' => 'Input Caption (Generated)',
                                    'key' => 'Simulasi Tombol (AutoX Key)',
                                    'scrollUp' => 'Scroll Up (AutoX)',
                                    'scrollDown' => 'Scroll Down (AutoX)',
                                ])
                                ->required()
                                ->live(),

                            TextInput::make('image_url')
                                ->label('URL Gambar')
                                ->visible(fn ($get) => $get('action') === 'uploadImage')
                                ->helperText('Biarkan kosong untuk otomatis ambil dari generated content')
                                ->nullable(),
                            // TEXT / INPUT
                            TextInput::make('text')
                                ->label('Teks')
                                ->visible(fn ($get) => in_array($get('action'), ['toast', 'clickText', 'input', 'inputCaption']))
                                ->helperText(fn ($get) => $get('action') === 'inputCaption'
                                    ? 'Biarkan kosong untuk otomatis ambil caption dari generated content'
                                    : null)
                                ->nullable(),
                            
                            TextInput::make('i')
                                ->label('Nomor Kontrol Scroll (opsional)')
                                ->numeric()
                                ->nullable()
                                ->visible(fn ($get) => in_array($get('action'), ['scrollUp', 'scrollDown'])),


                            // KEY SIMULATION - AutoX Function
                            Select::make('key_command')
                                ->label('Fungsi Tombol AutoX')
                                ->options([
                                    'back()' => 'Back',
                                    'home()' => 'Home',
                                    'powerDialog()' => 'Power',
                                    'notifications()' => 'Notifications',
                                    'quickSettings()' => 'Quick Settings',
                                    'recents()' => 'Recents',
                                    'dismissNotificationShade()' => 'DismissNotificationShade',
                                    'accessibilityShortcut()' => 'AccessibilityShortcut',
                                    'accessibilityButtonChooser()' => 'AccessibilityButtonChooser',
                                    'accessibilityButton()' => 'AccessibilityButton',
                                    'accessibilityAllApps()' => 'AccessibilityAllApps',
                                ])
                                ->searchable()
                                ->visible(fn ($get) => $get('action') === 'key')
                                ->required(fn ($get) => $get('action') === 'key')
                                ->live(),

                            // LAUNCH APP
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
                            ])->visible(fn ($get) => $get('action') === 'launchApp'),

                            // SLEEP
                            TextInput::make('ms')
                                ->label('Waktu Tunggu (ms)')
                                ->visible(fn ($get) => $get('action') === 'sleep')
                                ->numeric()
                                ->nullable(),

                            // TAP
                            Fieldset::make('Input Position')
                                ->schema([
                                    TextInput::make('x')
                                    ->label('X')
                                    ->numeric()
                                    ->nullable(),

                                    TextInput::make('y')
                                        ->label('Y')
                                        ->numeric()
                                        ->nullable(),
                                ])->visible(fn ($get) => $get('action') === 'tap'),

                            // SWIPE
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
                Tables\Columns\TextColumn::make('name')->label('Task Name'),
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
                    ->label('Start')
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
                            ->title('✅ Task is sent to the device')
                            ->body($record->apply_to_all 
                                ? 'Task is sent to all devices.' 
                                : "Task is sent to the device: {$record->device->android_id}")
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

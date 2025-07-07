<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Device;
use App\Models\Machine;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Forms\Components\Placeholder;
use Filament\Forms\Components\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\DeviceResource\Pages;
use Filament\Tables\Actions\Action as ActionsAction;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total device terdaftar';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('machine.name')->label('Machine')->searchable(),
                TextColumn::make('serial')->searchable(),
                TextColumn::make('android_id')->searchable(),
                // TextColumn::make('name')
                //     ->label('Name')
                //     ->formatStateUsing(fn ($state, $record) => "{$record->id}-{$record->model}-{$record->machine->name}")
                //     ->searchable(),

                TextColumn::make('model')->searchable(),

                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'connected' => 'success',
                    'disconnected' => 'danger',
                    default => 'gray'
                }),

                // TextColumn::make('connection_status')
                //     ->label('Internet (Cache)')
                //     ->getStateUsing(fn($record) => cache("ping_status_{$record->serial}"))
                //     ->badge()
                //     ->color(fn ($state) => match ($state) {
                //         'Connected' => 'success',
                //         'No Internet' => 'warning',
                //         'Disconnected' => 'danger',
                //         'Error' => 'gray',
                //         default => 'gray',
                //     }),

                TextColumn::make('ip_address')->sortable()->label('IP'),
                TextColumn::make('ssid')->label('SSID')->color('gray'),
            ])
            ->filters([
                SelectFilter::make('machine_id')
                    ->options(Machine::all()->pluck('name', 'id'))
                    ->label('Machine')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                SelectFilter::make('status')
                    ->options([
                        'connected' => 'Connected',
                        'disconnected' => 'Disconnected',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                ActionsAction::make('Screenshot')
                    ->label('Screenshot')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->modalHeading('Tangkapan Layar')
                    ->modalContent(fn ($record) => view('filament.resources.device-resource.modals.screenshot', [
                        'deviceId' => $record->id,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false), // tambahan opsional

                ActionsAction::make('apps')
                    ->label('Installed Apps')
                    ->icon('heroicon-m-rectangle-stack')
                    ->modalHeading('Installed Apps')
                    ->modalContent(fn (Device $record) => view('filament.resources.device-resource.modals.apps', [
                        'apps' => $record->getInstalledApps(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                ActionsAction::make('refresh')
                    ->label('Refresh Device')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Device $record) {
                        $record->refreshConnectionStatus(force: true);
                        $record->updateNetworkInfo();

                        Notification::make()
                            ->title("Refreshed: {$record->serial}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // ActionsAction::make('refresh_all_connections')
                //     ->label('🔄 Re-check Connections')
                //     ->requiresConfirmation()
                //     ->color('gray')
                //     ->action(function () {
                //         $devices = \App\Models\Device::all();

                //         $success = 0;
                //         $fail = 0;

                //         foreach ($devices as $device) {
                //             try {
                //                 $device->refreshConnectionStatus(force: true);
                //                 $device->updateNetworkInfo();
                //                 $success++;
                //             } catch (\Throwable $e) {
                //                 \Log::error("❌ Failed recheck for {$device->serial}: " . $e->getMessage());
                //                 $fail++;
                //             }
                //         }

                //         Notification::make()
                //             ->title("Re-check completed: {$success} success, {$fail} failed.")
                //             ->success()
                //             ->send();
                //     }),


            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
        ];
    }
}

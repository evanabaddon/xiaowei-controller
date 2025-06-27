<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Filament\Resources\DeviceResource\RelationManagers;
use App\Models\Device;
use App\Models\Machine;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
        return 'The number of devices';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('machine.name')->searchable(),
                TextColumn::make('serial')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('model')->searchable(),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'connected' => 'success',
                    'disconnected' => 'danger',
                }),
                TextColumn::make('last_seen_at')->dateTime('d-m-Y H:i:s')->hidden(),
                TextColumn::make('connection_status')
                    ->label('Internet')
                    ->state(function ($record) {
                        return $record->getConnectionStatus(); // fungsi yang tadi kamu buat
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Connected' => 'success',
                        'Disconnected' => 'danger',
                        'Unknown' => 'gray',
                        'Error' => 'warning',
                    }),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('ssid')
                    ->label('SSID')
                    ->color('gray')
            ])
            ->filters([
                SelectFilter::make('machine_id')
                    ->options(Machine::all()->pluck('name', 'id'))
                    ->label('Machine')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('status')
                    ->options([
                        'connected' => 'Connected',
                        'disconnected' => 'Disconnected',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                ActionsAction::make('apps')
                    ->label('Installed Apps')
                    ->icon('heroicon-m-rectangle-stack')
                    ->modalHeading('Installed Apps')
                    ->modalContent(fn (Device $record) => view('filament.resources.device-resource.modals.apps', [
                        'apps' => $record->getInstalledApps(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                ActionsAction::make('screen')
                    ->label('View Screen')
                    ->icon('heroicon-o-camera')
                    ->modalHeading('Last Device Screenshot')
                    ->modalContent(fn (Device $record) => view('filament.resources.device-resource.modals.screenshot', [
                        'image' => $record->getLastScreenshot(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ActionsAction::make('refresh_all_connections')
                    ->label('🔄 Re-check Connections')
                    ->action(function () {
                        $devices = \App\Models\Device::all();

                        foreach ($devices as $device) {
                            $device->refreshConnectionStatus(force: true);
                            $device->updateNetworkInfo();
                        }

                        Notification::make()
                            ->title('✅ All connections rechecked')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListDevices::route('/'),
        ];
    }

}

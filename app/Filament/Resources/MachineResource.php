<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MachineResource\Pages;
use App\Filament\Resources\MachineResource\RelationManagers;
use App\Models\Machine;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    public static function getNavigationBadge(): string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'The number of machines';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')->required(),
        
                    Select::make('ws_mode')
                        ->label('WebSocket Mode')
                        ->options([
                            'auto' => 'Auto (ws://IP:PORT)',
                            'custom' => 'Custom (Ngrok or other)',
                        ])
                        ->reactive()
                        ->required(),
        
                    TextInput::make('ip_address')
                        ->required()
                        ->visible(fn ($get) => $get('ws_mode') === 'auto'),
        
                    TextInput::make('port')
                        ->numeric()
                        ->default(22222)
                        ->visible(fn ($get) => $get('ws_mode') === 'auto'),
        
                    TextInput::make('custom_ws_url')
                        ->label('Custom WebSocket URL')
                        ->placeholder('wss://example.ngrok.io')
                        ->visible(fn ($get) => $get('ws_mode') === 'custom'),
                ]),
        
                Textarea::make('notes')->columnSpanFull(),
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('ws_url')->label('WebSocket URL')->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'unknown' => 'secondary',
                    }),
                TextColumn::make('last_checked_at')->label('Last Check')->since(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMachines::route('/'),
            'create' => Pages\CreateMachine::route('/create'),
            'edit' => Pages\EditMachine::route('/{record}/edit'),
        ];
    }
}

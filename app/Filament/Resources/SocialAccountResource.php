<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Enum\AgeRange;
use App\Models\Device;
use Filament\Forms\Form;
use App\Enum\ContentTone;
use Filament\Tables\Table;
use App\Models\SocialAccount;
use App\Enum\PoliticalLeaning;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SocialAccountResource\Pages;
use App\Filament\Resources\SocialAccountResource\RelationManagers;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Account Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->password()
                    ->label('Password (optional)'),

                Textarea::make('cookie')
                    ->label('Cookie (optional)')
                    ->rows(3),

                Select::make('platform_id')
                    ->relationship('platform', 'name')
                    ->required()
                    ->label('Platform'),

                Select::make('account_category_id')
                    ->relationship('accountCategory', 'name')
                    ->required()
                    ->label('Kategori Akun'),

                Select::make('device_id')
                    ->label('Dipakai di Device')
                    ->options(
                        Device::all()->mapWithKeys(function ($device) {
                            return [
                                $device->id => "{$device->id}-{$device->model}-{$device->machine->name}",
                            ];
                        })
                    )
                    ->searchable(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'banned' => 'Banned',
                        'suspended' => 'Suspended',
                        'pending' => 'Pending',
                    ])
                    ->default('pending')
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->rows(2),
                    
                Section::make('Persona Settings')
                    ->schema([
                        Fieldset::make('Demografi')
                            ->schema([
                                Select::make('persona.age_range')
                                    ->options([
                                        '18-25' => '18-25 Tahun',
                                        '26-35' => '26-35 Tahun',
                                        '36-45' => '36-45 Tahun',
                                        '45+' => '45+ Tahun'
                                    ])
                                    ->required(),
                                    
                               Select::make('persona.political_leaning')
                                    ->options(PoliticalLeaning::class)
                                    ->required(),
                            ])->columns(2),
    
                        Fieldset::make('Konten')
                            ->schema([
                                TagsInput::make('persona.interests')
                                    ->separator(','),
                                    
                                Select::make('persona.content_tone')
                                    ->options(ContentTone::class)
                                    ->required(),
                            ]),
    
                        Textarea::make('persona.persona_description')
                            ->label('Deskripsi Persona')
                            ->columnSpanFull()
                    ])
                    ->hidden(fn ($operation) => $operation === 'view')
            
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')->searchable()->sortable(),
                TextColumn::make('platform.name')->label('Platform')->sortable(),
                TextColumn::make('accountCategory.name')->label('Kategori')->sortable(),
                TextColumn::make('device.name')->label('Device')->formatStateUsing(function ($state, $record) {
                    $device = $record->device;
                    if (!$device) return '-';
                    
                    return "{$device->id}-{$device->model}-{$device->machine->name}";
                }),
                TextColumn::make('persona.age_range')
                    ->label('Age')
                    ->badge()
                    ->color('primary'),
                    
                TextColumn::make('persona.political_leaning')
                    ->label('Political')
                    ->formatStateUsing(fn ($state) => PoliticalLeaning::tryFrom($state)?->label())
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pro_government' => 'success',
                        'opposition' => 'danger',
                        default => 'gray'
                    }),
                    
                TextColumn::make('persona.content_tone')
                    ->label('Tone')
                    ->formatStateUsing(fn ($state) => is_object($state) 
                        ? $state->label() 
                        : ContentTone::tryFrom($state)?->label() ?? 'Unknown'
                    )
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'banned',
                        'warning' => 'suspended',
                        'gray' => 'pending',
                    ])
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('accountCategory')
                    ->relationship('accountCategory', 'name')
                    ->label('Kategori Akun'),
    
                SelectFilter::make('age_range')
                    ->label('Range Usia')
                    ->options(AgeRange::getOptions())
                    ->relationship('persona', 'age_range'),
    
                SelectFilter::make('political_leaning')
                    ->label('Kecenderungan Politik')
                    ->options(PoliticalLeaning::getOptions())
                    ->relationship('persona', 'political_leaning'),
    
                SelectFilter::make('content_tone')
                    ->label('Gaya Konten')
                    ->options(ContentTone::getOptions()) // Gunakan method yang sudah diperbaiki
                    ->relationship('persona', 'content_tone'),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'banned' => 'Banned',
                        'suspended' => 'Suspended',
                        'pending' => 'Pending',
                    ])
                    ->label('Status Akun'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                BulkAction::make('assign_device')
                    ->label('🖥️ Assign to Device')
                    ->form([
                        Select::make('device_id')
                            ->label('Pilih Device')
                            ->options(function () {
                                return Device::with('machine')->get()->mapWithKeys(function ($device) {
                                    return [
                                        $device->id => "{$device->id}-{$device->model}-{$device->machine->name}"
                                    ];
                                })->toArray();
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $record->update([
                                'device_id' => $data['device_id'],
                                'status' => 'active',
                            ]);
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('primary'),
                BulkAction::make('unassign_device')
                    ->label('❌ Unassign from Device')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->update([
                                'device_id' => null,
                                'status' => 'pending',
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->deselectRecordsAfterCompletion(),
                
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('persona');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialAccounts::route('/'),
            'create' => Pages\CreateSocialAccount::route('/create'),
            'view' => Pages\ViewSocialAccount::route('/{record}'),
            'edit' => Pages\EditSocialAccount::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ContentTask;
use App\Models\SocialAccount;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\GenerateContentForAccountJob;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ContentTaskResource\Pages;
use App\Filament\Resources\ContentTaskResource\RelationManagers;

class ContentTaskResource extends Resource
{
    protected static ?string $model = ContentTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('social_account_id')
                    ->options(SocialAccount::pluck('username', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('mode')
                    ->options([
                        'once' => 'Sekali (Generate Manual)',
                        'scheduled' => 'Terjadwal Harian',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('daily_quota')
                    ->numeric()
                    ->minValue(1)
                    ->label('Jumlah Konten per Hari')
                    ->visible(fn (Forms\Get $get) => $get('mode') === 'scheduled'),

                Toggle::make('active')
                    ->label('Aktifkan Sekarang?')
                    ->helperText('Kontrol mulai/berhenti proses generasi konten otomatis.')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('socialAccount.username'),
                TextColumn::make('mode'),
                TextColumn::make('daily_quota'),
                IconColumn::make('active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')   // hijau
                    ->falseColor('danger')   // merah
                    ->tooltip(fn (bool $state): string => $state ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('generate_now')
                    ->label('Generate Sekarang')
                    ->icon('heroicon-m-sparkles')
                    ->visible(fn ($record) => $record->mode === 'once')
                    ->action(function ($record) {
                        $account = $record->socialAccount;
                
                        if ($account) {
                            GenerateContentForAccountJob::dispatch($account);
                
                            Notification::make()
                                ->title("Konten sedang digenerate untuk {$account->username}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title("Social account tidak ditemukan.")
                                ->danger()
                                ->send();
                        }
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
            'index' => Pages\ListContentTasks::route('/'),
            'create' => Pages\CreateContentTask::route('/create'),
            'edit' => Pages\EditContentTask::route('/{record}/edit'),
        ];
    }
}

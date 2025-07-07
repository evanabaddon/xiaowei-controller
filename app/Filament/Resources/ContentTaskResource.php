<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ContentTask;
use App\Models\SocialAccount;
use App\Models\AutomationTask;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
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

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $navigationGroup = 'Content Management 🤖';

    protected static ?string $title = 'Content Scheduller';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('mode')
                ->options([
                    'once' => 'Sekali (Generate Manual)',
                    'scheduled' => 'Terjadwal Harian',
                ])
                ->required()
                ->live(),
    
            // Pilih Automation Task lebih dulu
            Select::make('automation_task_id')
                ->relationship('automationTask', 'name')
                ->required()
                ->live(), // agar perubahan trigger update field lain
    
            // Social Accounts difilter berdasarkan platform dari automation task
            Select::make('social_account_ids')
                ->label('Social Accounts')
                ->options(function (Get $get) {
                    $automationTaskId = $get('automation_task_id');
                    if (!$automationTaskId) {
                        return [];
                    }
                    $automationTask = \App\Models\AutomationTask::find($automationTaskId);
                    if (!$automationTask || !$automationTask->platform_id) {
                        return [];
                    }
                    $accounts = \App\Models\SocialAccount::where('platform_id', $automationTask->platform_id)
                        ->pluck('username', 'id')
                        ->toArray();

                    // Tambahkan opsi "Semua Akun" di awal
                    return ['all' => 'Semua Akun'] + $accounts;
                })
                ->multiple()
                ->searchable()
                ->required(),
    
            TextInput::make('daily_quota')
                ->numeric()
                ->minValue(1)
                ->label('Jumlah Konten per Hari')
                ->visible(fn (Get $get) => $get('mode') === 'scheduled'),
    
            Toggle::make('active')
                ->label('Aktifkan Sekarang?')
                ->helperText('Kontrol mulai/berhenti proses generasi konten otomatis.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('automationTask.name'),
                TextColumn::make('mode'),
                TextColumn::make('daily_quota'),
                TextColumn::make('last_generated_at')->since(),
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
                        Log::info('[Filament] Action generate_now dipanggil', [
                            'content_task_id' => $record->id,
                            'social_account_ids' => $record->social_account_ids,
                            'automation_task_id' => $record->automation_task_id,
                        ]);
            
                        $accountIds = $record->social_account_ids ?? [];
            
                        // Jika user memilih "Semua Akun"
                        if (in_array('all', $accountIds)) {
                            $automationTask = $record->automationTask;
                            $accountIds = SocialAccount::where('platform_id', $automationTask->platform_id)
                                ->pluck('id')
                                ->toArray();
                            Log::info('[Filament] User memilih Semua Akun', [
                                'platform_id' => $automationTask->platform_id,
                                'account_ids' => $accountIds,
                            ]);
                        }
            
                        $accounts = SocialAccount::whereIn('id', $accountIds)->get();
            
                        if ($accounts->isEmpty()) {
                            Log::warning('[Filament] Tidak ada akun sosial ditemukan', [
                                'account_ids' => $accountIds,
                            ]);
                            Notification::make()
                                ->title("Social account tidak ditemukan.")
                                ->danger()
                                ->send();
                            return;
                        }
            
                        foreach ($accounts as $account) {
                            Log::info('[Filament] Dispatch job untuk akun', [
                                'account_id' => $account->id,
                                'username' => $account->username,
                                'has_persona' => (bool) $account->persona,
                            ]);
                            if ($account->persona) {
                                GenerateContentForAccountJob::dispatch(
                                    $account->persona,
                                    $accountIds, // atau $record->social_account_ids
                                    $record->automationTask
                                );
                            }
                        }
            
                        Log::info('[Filament] Konten sedang digenerate', [
                            'total_accounts' => $accounts->count(),
                        ]);
            
                        Notification::make()
                            ->title("Konten sedang digenerate untuk " . $accounts->count() . " akun")
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
            'index' => Pages\ListContentTasks::route('/'),
            'create' => Pages\CreateContentTask::route('/create'),
            'edit' => Pages\EditContentTask::route('/{record}/edit'),
        ];
    }
}

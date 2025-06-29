<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\GeneratedContent;
use Filament\Resources\Resource;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\GeneratedContentResource\Pages;
use App\Filament\Resources\GeneratedContentResource\RelationManagers;

class GeneratedContentResource extends Resource
{
    protected static ?string $model = GeneratedContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('socialAccount.username')
                    ->label('Social Account ID')
                    ->numeric()
                    ->disabled()
                    ->columnSpanFull(),

                Textarea::make('caption')
                    ->label('Generated Caption')
                    ->autosize()
                    ->columnSpanFull(),

                TextInput::make('tags')
                    ->label('Tags (Comma Separated)')
                    ->placeholder('contoh: kopi, teknologi, pro government')
                    ->columnSpanFull(),

                TextInput::make('image_url')
                    ->label('Image URL')
                    ->url()
                    ->columnSpanFull(),

                View::make('components.generated-image-preview')
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'failed' => 'Failed',
                    ])
                    ->native(false)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('socialAccount.username')->label('Username')->searchable(),
                ImageColumn::make('image_url')->label('Image')->height(100),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('socialAccount');
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
            'index' => Pages\ListGeneratedContents::route('/'),
            'create' => Pages\CreateGeneratedContent::route('/create'),
            'edit' => Pages\EditGeneratedContent::route('/{record}/edit'),
            'view' => Pages\ViewGeneratedContent::route('/{record}'),
        ];
    }
}

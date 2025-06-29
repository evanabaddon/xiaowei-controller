<?php

namespace App\Filament\Resources\SocialAccountResource\Pages;

use Filament\Actions;
use Filament\Forms\Form;
use App\Enum\ContentTone;
use App\Enum\PoliticalLeaning;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\SocialAccountResource;

class ViewSocialAccount extends ViewRecord
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing persona data when opening edit form
        $data['persona'] = $this->record->persona?->toArray() ?? [];
        
        return $data;
    }

    public function form(Form $form): Form
    {
        // dd($this->record->persona); 
        return $form
            ->schema([
                // Section untuk data utama akun
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->disabled(),
                        TextInput::make('password')
                            ->label('Password')
                            ->disabled(),
                    ])->columns(2),
                
                // Section untuk Persona
                Section::make('Persona Details')
                    ->schema([
                        Fieldset::make('Demographics')
                            ->schema([
                                TextInput::make('persona.age_range')
                                    ->label('Age Range')
                                    ->formatStateUsing(fn ($state) => $state ?? '-')
                                    ->disabled(),
                                    
                                TextInput::make('persona.political_leaning')
                                    ->label('Political Leaning')
                                    ->formatStateUsing(fn ($state) => 
                                        $state ? PoliticalLeaning::tryFrom($state)?->label() : '-'
                                    )
                                    ->disabled(),
                            ])->columns(2),
                            
                        Fieldset::make('Content Preferences')
                            ->schema([
                                TextInput::make('persona.content_tone')
                                    ->label('Content Tone')
                                    ->formatStateUsing(fn ($state) => 
                                        $state ? ContentTone::tryFrom($state)?->label() : '-'
                                    )
                                    ->disabled(),
                                    
                                TagsInput::make('persona.interests')
                                    ->label('Interests')
                                    ->disabled()
                                    ->default(fn ($record) => $record->persona?->interests ?? []),
                            ]),
                            
                        Textarea::make('persona.persona_description')
                            ->label('Description')
                            ->disabled()
                            ->columnSpanFull()
                    ])
                    ->visible(fn ($record) => $record->persona !== null)
            ]);
    }
}

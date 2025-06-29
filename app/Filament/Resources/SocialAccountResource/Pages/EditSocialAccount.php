<?php

namespace App\Filament\Resources\SocialAccountResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SocialAccountResource;

class EditSocialAccount extends EditRecord
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing persona data when opening edit form
        $data['persona'] = $this->record->persona?->toArray() ?? [];
        
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $personaData = $data['persona'] ?? [];
        unset($data['persona']);

        // Pastikan JSON fields valid
        $personaData['interests'] = array_map('trim', explode(',', $personaData['interests'] ?? '[]'));
        $personaData['age_range'] = (string) ($personaData['age_range'] ?? ''); // pastikan string
        $personaData['political_leaning'] = (string) ($personaData['political_leaning'] ?? '');
        $personaData['content_tone'] = (string) ($personaData['content_tone'] ?? '');

        parent::handleRecordUpdate($record, $data);

        // Update or create persona
        if ($record->persona) {
            $record->persona()->update($personaData);
        } else {
            $record->persona()->create($personaData);
        }

        return $record;
    }
}

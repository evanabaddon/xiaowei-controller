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

        parent::handleRecordUpdate($record, $data);
        
        // Handle both update and create scenario
        if ($record->persona) {
            $record->persona()->update($personaData);
        } else {
            $record->persona()->create($personaData);
        }

        return $record;
    }
}

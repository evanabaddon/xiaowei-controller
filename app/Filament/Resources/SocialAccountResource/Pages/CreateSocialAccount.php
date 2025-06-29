<?php

namespace App\Filament\Resources\SocialAccountResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SocialAccountResource;

class CreateSocialAccount extends CreateRecord
{
    protected static string $resource = SocialAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $personaData = $data['persona'];
        unset($data['persona']);

        $account = parent::handleRecordCreation($data);
        $account->persona()->create($personaData);

        return $account;
    }


}

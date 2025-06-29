<?php

namespace App\Filament\Resources\GeneratedContentResource\Pages;

use App\Filament\Resources\GeneratedContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeneratedContents extends ListRecords
{
    protected static string $resource = GeneratedContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

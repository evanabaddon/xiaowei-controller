<?php

namespace App\Filament\Resources\GeneratedContentResource\Pages;

use App\Filament\Resources\GeneratedContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGeneratedContent extends ViewRecord
{
    protected static string $resource = GeneratedContentResource::class;

    // Arahkan ke custom view
    protected static string $view = 'filament.resources.generated-content.pages.view-generated-content';

    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace App\Filament\Resources\GeneratedContentResource\Pages;

use App\Filament\Resources\GeneratedContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGeneratedContent extends ViewRecord
{
    protected static string $resource = GeneratedContentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $parsed = json_decode($data['response'], true);

        return [
            ...$data,
            'caption' => $parsed['caption'] ?? '-',
            'tags' => implode(', ', $parsed['tags'] ?? []),
        ];
    }

}

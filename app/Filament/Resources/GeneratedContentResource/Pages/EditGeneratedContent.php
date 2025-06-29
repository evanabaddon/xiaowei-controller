<?php

namespace App\Filament\Resources\GeneratedContentResource\Pages;

use App\Filament\Resources\GeneratedContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeneratedContent extends EditRecord
{
    protected static string $resource = GeneratedContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
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

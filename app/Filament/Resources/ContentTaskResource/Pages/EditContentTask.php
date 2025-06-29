<?php

namespace App\Filament\Resources\ContentTaskResource\Pages;

use App\Filament\Resources\ContentTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentTask extends EditRecord
{
    protected static string $resource = ContentTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

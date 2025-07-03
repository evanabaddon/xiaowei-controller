<?php

namespace App\Filament\Resources\AutomationTaskResource\Pages;

use App\Filament\Resources\AutomationTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutomationTask extends EditRecord
{
    protected static string $resource = AutomationTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AutomationTaskResource\Pages;

use App\Filament\Resources\AutomationTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutomationTasks extends ListRecords
{
    protected static string $resource = AutomationTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

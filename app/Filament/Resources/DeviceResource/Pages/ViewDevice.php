<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDevice extends ViewRecord
{
    protected static string $resource = DeviceResource::class;

    protected static string $view = 'filament.resources.device-resource.pages.view-device';



    public function getInstalledApps(): array
    {
        /** @var Device $device */
        $device = $this->record;
        return $device->getInstalledApps();
    }
}

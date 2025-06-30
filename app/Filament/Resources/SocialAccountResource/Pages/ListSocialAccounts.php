<?php

namespace App\Filament\Resources\SocialAccountResource\Pages;

use Filament\Actions;
use App\Enum\AgeRange;
use App\Models\Platform;
use App\Enum\PoliticalLeaning;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\SocialAccountResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use HayderHatem\FilamentExcelImport\Actions\Concerns\CanImportExcelRecords;

class ListSocialAccounts extends ListRecords
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // Actions\ImportAction::make()
            //     ->importer(UserImporter::class),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All Accounts')
                ->icon('heroicon-o-user-group')
                ->badge($this->getModel()::count()),
        ];

        // Tabs by Platform
        foreach (Platform::all() as $platform) {
            $tabs['platform_'.$platform->id] = Tab::make($platform->name)
                // ->icon($platform->icon) // Jika ada field icon
                ->modifyQueryUsing(fn (Builder $query) => $query->where('platform_id', $platform->id))
                ->badge($this->getModel()::where('platform_id', $platform->id)->count());
        }

        return $tabs;
    }
}

<?php

namespace App\Filament\Resources\SocialAccountResource\Pages;

use Filament\Actions;
use App\Models\Platform;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\SocialAccountResource;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListSocialAccounts extends ListRecords
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'All Platform' => Tab::make()
                ->label('All')
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    
        foreach (Platform::all() as $platform) {
            $tabs[$platform->name] = Tab::make()
                ->label($platform->name)
                ->modifyQueryUsing(function (Builder $query) use ($platform) {
                    $query->where('platform_id', $platform->id);
                });
        }
    
        return $tabs;
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                     ->label('Account Management')
                     ->icon('heroicon-o-users')->collapsed(true),
                NavigationGroup::make()
                     ->label('Content Management')
                     ->icon('heroicon-o-chat-bubble-left-right')->collapsed(true),
            ]);
        });
    }
}

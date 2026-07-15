<?php

namespace App\Providers;

use App\Models\ProductLotteryPrize;
use App\Observers\ProductLotteryPrizeObserver;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

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
        ProductLotteryPrize::observe(ProductLotteryPrizeObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => view('filament.hooks.sidebar-navigation-accordion')->render(),
        );
    }
}

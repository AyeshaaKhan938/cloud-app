<?php

namespace App\Providers\Filament;

use App\Filament\Admin\GlobalSearch\VmfsGlobalSearchProvider;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Navigation\AdminNavigationItems;
use App\Filament\Admin\Pages\CloudDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('VMFS USA')
            ->viteTheme('resources/css/app.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->globalSearch(VmfsGlobalSearchProvider::class)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->globalSearchDebounce('350ms')
            ->renderHook(
                'panels::body.start',
                fn (): string => auth()->check()
                    ? ''
                    : view('filament.admin.auth.brand-panel')->render(),
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => auth()->check()
                    ? view('filament.hooks.sidebar-navigation-accordion')->render()
                    : '',
            )
            ->navigationGroups(AdminNavigationGroups::ordered())
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->navigationItems(AdminNavigationItems::definitions())
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                CloudDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

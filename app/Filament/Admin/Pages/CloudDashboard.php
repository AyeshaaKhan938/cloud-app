<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\AdminQuickActionsWidget;
use App\Filament\Admin\Widgets\CustomerHomeStatsWidget;
use App\Filament\Admin\Widgets\CustomerQuickActionsWidget;
use App\Filament\Admin\Widgets\DashboardBusinessStats;
use App\Filament\Admin\Widgets\DashboardWelcomeWidget;
use App\Filament\Admin\Widgets\RecentDemoOrdersTable;
use App\Filament\Admin\Widgets\RevenueTrendChart;
use App\Filament\Admin\Widgets\SupportQueueOverviewWidget;
use App\Services\Users\UserCloudScope;
use Filament\Pages\Dashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

final class CloudDashboard extends Dashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $slug = 'dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getSubheading(): string|Htmlable|null
    {
        if (app(UserCloudScope::class)->hasFullCloudAccess()) {
            return 'Platform overview — sales, machines, support queue, and recent activity.';
        }

        return 'Your machines, sales, and support — everything in one place.';
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        if (app(UserCloudScope::class)->hasFullCloudAccess()) {
            return [
                DashboardWelcomeWidget::class,
                DashboardBusinessStats::class,
                AdminQuickActionsWidget::class,
                SupportQueueOverviewWidget::class,
                RevenueTrendChart::class,
                RecentDemoOrdersTable::class,
            ];
        }

        return [
            DashboardWelcomeWidget::class,
            CustomerHomeStatsWidget::class,
            CustomerQuickActionsWidget::class,
            RevenueTrendChart::class,
        ];
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}

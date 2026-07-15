<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Widgets\NewDeviceMonthlyTrendChart;
use App\Filament\Admin\Widgets\OrdersByDayChart;
use App\Filament\Admin\Widgets\RecentDemoOrdersTable;
use App\Filament\Admin\Widgets\RevenueTrendChart;
use App\Filament\Admin\Widgets\SalesMixChart;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class DataDashboard extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $navigationLabel = 'Overview dashboard';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Reports;

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Data Dashboard';

    protected static ?string $slug = 'reports/data-dashboard';

    public function getSubheading(): ?string
    {
        return 'Charts and trends for revenue, lottery draws, orders, and device growth.';
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            RevenueTrendChart::class,
            SalesMixChart::class,
            OrdersByDayChart::class,
            NewDeviceMonthlyTrendChart::class,
            RecentDemoOrdersTable::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 2;
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Reports;
    }
}

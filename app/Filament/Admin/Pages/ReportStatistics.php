<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Widgets\DashboardBusinessStats;
use App\Filament\Admin\Widgets\UserSalesRankTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ReportStatistics extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Sales statistics';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Reports;

    protected static ?int $navigationSort = 70;

    protected static ?string $title = 'Statistics';

    protected static ?string $slug = 'reports/statistics';

    public function getSubheading(): ?string
    {
        return 'High-level KPIs and top-performing accounts at a glance.';
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            DashboardBusinessStats::class,
            UserSalesRankTable::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Reports;
    }
}

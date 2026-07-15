<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\OperatorAlertsOverview;
use App\Filament\Admin\Widgets\OverviewStats;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            OverviewStats::class,
            OperatorAlertsOverview::class,
        ];
    }
}

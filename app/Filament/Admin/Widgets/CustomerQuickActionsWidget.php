<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\UserFeature;
use App\Filament\Admin\Pages\BusinessAnalytics;
use App\Filament\Admin\Pages\DataDashboard;
use App\Filament\Admin\Pages\MachineMap;
use App\Filament\Admin\Resources\Machines\MachineResource;
use App\Filament\Admin\Resources\MyWorkOrders\MyWorkOrderResource;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;
use Filament\Widgets\Widget;

final class CustomerQuickActionsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -40;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.customer-quick-actions';

    /**
     * @return list<array{label: string, description: string, url: string, icon: string}>
     */
    public function getActions(): array
    {
        $featureAccess = app(FeatureAccess::class);
        $actions = [];

        if ($featureAccess->allowsAnyNavigation(
            UserFeature::MachinesView,
            UserFeature::MachinesCreate,
            UserFeature::MachineSlots,
        )) {
            $actions[] = [
                'label' => 'All machines',
                'description' => 'View status, stock, and slot configuration',
                'url' => MachineResource::getUrl(),
                'icon' => 'heroicon-o-cpu-chip',
            ];
        }

        if ($featureAccess->allowsNavigation(UserFeature::Reports)) {
            $actions[] = [
                'label' => 'Sales & profit',
                'description' => 'Revenue and profit by machine or portfolio',
                'url' => BusinessAnalytics::getUrl(),
                'icon' => 'heroicon-o-chart-bar-square',
            ];

            $actions[] = [
                'label' => 'Overview dashboard',
                'description' => 'Charts and trends for your sales',
                'url' => DataDashboard::getUrl(),
                'icon' => 'heroicon-o-chart-pie',
            ];
        }

        if ($featureAccess->allowsAnyNavigation(
            UserFeature::MachinesView,
            UserFeature::MachinesCreate,
            UserFeature::MachineSlots,
        )) {
            $actions[] = [
                'label' => 'Map view',
                'description' => 'See machine locations on the map',
                'url' => MachineMap::getUrl(),
                'icon' => 'heroicon-o-map',
            ];
        }

        if (! app(UserCloudScope::class)->hasFullCloudAccess()) {
            $actions[] = [
                'label' => 'My support tickets',
                'description' => 'Submit or follow up on machine issues',
                'url' => MyWorkOrderResource::getUrl(),
                'icon' => 'heroicon-o-lifebuoy',
            ];
        }

        return $actions;
    }
}

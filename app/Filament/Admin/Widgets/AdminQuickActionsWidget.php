<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\BusinessAnalytics;
use App\Filament\Admin\Pages\NotificationConfiguration;
use App\Filament\Admin\Pages\SupportAvailability;
use App\Filament\Admin\Resources\Machines\MachineResource;
use App\Filament\Admin\Resources\WorkOrders\WorkOrderResource;
use App\Services\Users\UserCloudScope;
use Filament\Widgets\Widget;

final class AdminQuickActionsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -38;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.admin-quick-actions';

    public static function canView(): bool
    {
        return app(UserCloudScope::class)->hasFullCloudAccess();
    }

    /**
     * @return list<array{label: string, description: string, url: string, icon: string}>
     */
    public function getActions(): array
    {
        return [
            [
                'label' => 'Support queue',
                'description' => 'Open tickets sorted by priority',
                'url' => WorkOrderResource::getUrl(),
                'icon' => 'heroicon-o-lifebuoy',
            ],
            [
                'label' => 'Live chat availability',
                'description' => 'Set agents online for live support',
                'url' => SupportAvailability::getUrl(),
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
            [
                'label' => 'Sales & profit',
                'description' => 'Revenue and margin across all machines',
                'url' => BusinessAnalytics::getUrl(),
                'icon' => 'heroicon-o-chart-bar-square',
            ],
            [
                'label' => 'All machines',
                'description' => 'Fleet status, stock, and slot setup',
                'url' => MachineResource::getUrl(),
                'icon' => 'heroicon-o-cpu-chip',
            ],
            [
                'label' => 'Alerts & email',
                'description' => 'Notification rules and daily analytics email',
                'url' => NotificationConfiguration::getUrl(),
                'icon' => 'heroicon-o-bell-alert',
            ],
        ];
    }
}

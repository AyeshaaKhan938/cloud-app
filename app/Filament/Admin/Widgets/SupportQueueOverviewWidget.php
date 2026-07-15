<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Support\SupportQueueService;
use App\Services\Users\UserCloudScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SupportQueueOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Support queue';

    protected ?string $description = 'Open tickets and live chat demand right now';

    public static function canView(): bool
    {
        return app(UserCloudScope::class)->hasFullCloudAccess();
    }

    protected function getStats(): array
    {
        $queue = app(SupportQueueService::class);

        return [
            Stat::make('Open tickets', (string) $queue->openCount())
                ->description('Waiting in support queue')
                ->color('warning'),
            Stat::make('Urgent tickets', (string) $queue->urgentCount())
                ->description('Needs immediate attention')
                ->color('danger'),
            Stat::make('Live chat waiting', (string) $queue->liveChatRequestedCount())
                ->description($queue->isLiveChatAvailable() ? 'Agents online' : 'No agents online')
                ->color($queue->isLiveChatAvailable() ? 'success' : 'gray'),
        ];
    }
}

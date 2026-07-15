<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\ProductLotteryCode;
use App\Services\Users\UserCloudScope;
use Filament\Widgets\ChartWidget;

/**
 * Revenue + Lottery draw activity over the last 7 days — live data.
 *
 * Two datasets:
 *   - Revenue (bar)  : sum of completed orders' prize_amount per day
 *   - Draws (line)   : count of redeemed lottery codes per day
 */
final class RevenueTrendChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -35;

    protected ?string $heading = 'Revenue & Draws';

    protected ?string $description = 'Last 7 days · revenue (bars) and lottery draws (line)';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $scope = app(UserCloudScope::class);
        $machineNumbers = $scope->ownedMachineNumbers();

        $labels = [];
        $revenue = [];
        $draws = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D j');   // e.g. "Mon 14"

            $revenue[] = (float) $scope->scopeOrders(
                Order::query()->completed()->whereDate('completed_at', $date),
            )->sum('prize_amount');

            $drawsQuery = ProductLotteryCode::query()->whereDate('redeemed_at', $date);
            if (! $scope->hasFullCloudAccess()) {
                $drawsQuery->when(
                    $machineNumbers === [],
                    fn ($query) => $query->whereRaw('0 = 1'),
                    fn ($query) => $query->whereIn('machine_no', $machineNumbers),
                );
            }

            $draws[] = $drawsQuery->count();
        }

        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Revenue ($)',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(0, 122, 204, 0.55)',
                    'borderColor' => 'rgba(0, 122, 204, 0.9)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y',
                ],
                [
                    'type' => 'line',
                    'label' => 'Draws',
                    'data' => $draws,
                    'fill' => false,
                    'tension' => 0.4,
                    'borderColor' => 'rgba(34, 197, 94, 0.9)',
                    'borderWidth' => 2,
                    'pointRadius' => 3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'title' => ['display' => true, 'text' => 'Revenue ($)'],
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'title' => ['display' => true, 'text' => 'Draws'],
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';   // Mixed charts need a base type; datasets override individually
    }
}

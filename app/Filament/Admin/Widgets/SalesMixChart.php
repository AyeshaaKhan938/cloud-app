<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\ProductLotteryCode;
use Filament\Widgets\ChartWidget;

/**
 * Lottery code pool status — live breakdown of all codes across active lotteries.
 */
final class SalesMixChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -32;

    protected ?string $heading = 'Lottery Code Pool';

    protected ?string $description = 'Status of all codes across active lotteries';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $available = ProductLotteryCode::whereNull('redeemed_at')->count();
        $redeemed = ProductLotteryCode::whereNotNull('redeemed_at')->whereNull('dispensed_at')->count();
        $dispensed = ProductLotteryCode::whereNotNull('dispensed_at')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Codes',
                    'data' => [$available, $redeemed, $dispensed],
                    'backgroundColor' => ['#3b82f6', '#f59e0b', '#22c55e'],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Available', 'Redeemed', 'Dispensed'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Machine;
use Filament\Widgets\ChartWidget;

/**
 * New machines registered per month — last 6 months.
 */
final class NewDeviceMonthlyTrendChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -33;

    protected ?string $heading = 'New Device Monthly Trend';

    protected ?string $description = 'Machines added in the last 6 months';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $labels = [];
        $counts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $counts[] = Machine::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'New devices',
                    'data' => $counts,
                    'fill' => true,
                    'tension' => 0.4,
                    'borderColor' => 'rgba(99, 102, 241, 0.9)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'borderWidth' => 2,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgba(99, 102, 241, 1)',
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
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => 'Devices'],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

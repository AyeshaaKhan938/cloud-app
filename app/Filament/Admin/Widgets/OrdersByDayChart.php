<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\MachineSlot;
use Filament\Widgets\ChartWidget;

/**
 * Slot inventory health snapshot — live counts from machine_slots.
 */
final class OrdersByDayChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -31;

    protected ?string $heading = 'Slot Inventory';

    protected ?string $description = 'Current status across all machines';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        // Stocked: active, no fault, has product, stock above threshold
        $stocked = MachineSlot::where('is_active', true)
            ->where('is_fault', false)
            ->whereNotNull('product_id')
            ->whereColumn('current_stock', '>', 'stock_alarm_threshold')
            ->count();

        // Low stock: active, no fault, has product, at/below threshold but > 0
        $lowStock = MachineSlot::where('is_active', true)
            ->where('is_fault', false)
            ->whereNotNull('product_id')
            ->whereColumn('current_stock', '<=', 'stock_alarm_threshold')
            ->where('current_stock', '>', 0)
            ->count();

        // Empty: active, has product, zero stock
        $empty = MachineSlot::where('is_active', true)
            ->whereNotNull('product_id')
            ->where('current_stock', 0)
            ->count();

        // Fault or inactive
        $fault = MachineSlot::where(function ($q): void {
            $q->where('is_fault', true)->orWhere('is_active', false);
        })->count();

        return [
            'datasets' => [
                [
                    'label' => 'Slots',
                    'data' => [$stocked, $lowStock, $empty, $fault],
                    'backgroundColor' => ['#22c55e', '#f59e0b', '#ef4444', '#6b7280'],
                    'borderWidth' => 0,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => ['Stocked', 'Low Stock', 'Empty', 'Fault / Inactive'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

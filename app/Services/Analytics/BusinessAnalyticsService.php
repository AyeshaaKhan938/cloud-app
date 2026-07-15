<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Machine;
use App\Models\MachineAlarm;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\User;
use App\Services\Users\UserCloudScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class BusinessAnalyticsService
{
    public function __construct(
        private readonly UserCloudScope $cloudScope,
    ) {}

    /**
     * @param  list<int>  $machineIds  Empty = all machines visible to the actor.
     * @return array{
     *     period: array{from: string, to: string, days: int},
     *     machine_count: int,
     *     portfolio: array<string, mixed>,
     *     per_unit: list<array<string, mixed>>
     * }
     */
    public function buildReport(
        CarbonInterface $from,
        CarbonInterface $to,
        array $machineIds = [],
        ?User $user = null,
    ): array {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        $machines = $this->resolveMachines($machineIds, $user);
        $machineNumbers = $machines->pluck('machine_number')->all();
        $machineIdsResolved = $machines->pluck('id')->all();

        $portfolio = $this->metricsForMachines($machineNumbers, $machineIdsResolved, $from, $to);

        $perUnit = $machines
            ->map(fn (Machine $machine): array => [
                'machine' => [
                    'id' => $machine->id,
                    'number' => $machine->machine_number,
                    'name' => $machine->machine_name,
                    'is_enabled' => $machine->is_enabled,
                    'last_seen_at' => $machine->last_seen_at?->toIso8601String(),
                ],
                'metrics' => $this->metricsForMachines(
                    [$machine->machine_number],
                    [$machine->id],
                    $from,
                    $to,
                ),
            ])
            ->values()
            ->all();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => max(1, (int) $from->diffInDays($to) + 1),
            ],
            'machine_count' => $machines->count(),
            'portfolio' => $portfolio,
            'per_unit' => $perUnit,
        ];
    }

    /**
     * @param  list<int>  $machineIds
     * @return Collection<int, Machine>
     */
    private function resolveMachines(array $machineIds, ?User $user = null): Collection
    {
        $query = Machine::query()->orderBy('machine_number');

        $query = $this->cloudScope->scopeMachines($query, $user);

        if ($machineIds !== []) {
            $query->whereIn('id', $machineIds);
        }

        return $query->get();
    }

    /**
     * @param  list<string>  $machineNumbers
     * @param  list<int>  $machineIds
     * @return array<string, mixed>
     */
    private function metricsForMachines(
        array $machineNumbers,
        array $machineIds,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        if ($machineNumbers === []) {
            return $this->emptyMetrics();
        }

        $completed = Order::query()
            ->whereIn('machine_no', $machineNumbers)
            ->completed()
            ->whereBetween('completed_at', [$from, $to]);

        $failed = Order::query()
            ->whereIn('machine_no', $machineNumbers)
            ->where('status', 'failed')
            ->whereBetween('created_at', [$from, $to]);

        $revenue = $this->sumCompletedOrderRevenue($machineNumbers, $from, $to);
        $completedCount = (int) (clone $completed)->count();
        $failedCount = (int) $failed->count();
        $totalTransactions = $completedCount + $failedCount;

        $cogs = (float) DB::table('orders')
            ->leftJoin('machine_slots', 'orders.machine_slot_id', '=', 'machine_slots.id')
            ->leftJoin('products', 'products.id', '=', 'machine_slots.product_id')
            ->whereIn('orders.machine_no', $machineNumbers)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$from, $to])
            ->sum(DB::raw('COALESCE(products.cost, 0)'));

        $grossProfit = $revenue - $cogs;
        $marginPercent = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0.0;

        $paymentBreakdown = Order::query()
            ->whereIn('machine_no', $machineNumbers)
            ->completed()
            ->whereBetween('completed_at', [$from, $to])
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $daysInPeriod = max(1, (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
        $dailyAvgRevenue = $revenue / $daysInPeriod;
        $dailyAvgOrders = $completedCount / $daysInPeriod;

        $slotQuery = MachineSlot::query()
            ->whereIn('machine_id', $machineIds)
            ->where('is_active', true)
            ->whereNotNull('product_id');

        $slotsTotal = (int) (clone $slotQuery)->count();
        $slotsInStock = (int) (clone $slotQuery)->where('current_stock', '>', 0)->count();
        $slotsLow = (int) (clone $slotQuery)
            ->whereColumn('current_stock', '<=', 'stock_alarm_threshold')
            ->where('current_stock', '>', 0)
            ->count();
        $slotsEmpty = (int) (clone $slotQuery)->where('current_stock', 0)->count();
        $slotsFault = (int) MachineSlot::query()
            ->whereIn('machine_id', $machineIds)
            ->where('is_fault', true)
            ->count();

        $activeAlarms = (int) MachineAlarm::query()
            ->whereIn('machine_id', $machineIds)
            ->whereNull('acknowledged_at')
            ->count();

        $machinesOnline = (int) Machine::query()
            ->whereIn('id', $machineIds)
            ->where('is_enabled', true)
            ->count();

        return [
            'sales' => [
                'revenue' => round($revenue, 2),
                'orders' => $completedCount,
                'avg_order_value' => $completedCount > 0 ? round($revenue / $completedCount, 2) : 0.0,
            ],
            'costs' => [
                'cogs' => round($cogs, 2),
                'gross_profit' => round($grossProfit, 2),
                'margin_percent' => $marginPercent,
            ],
            'transactions' => [
                'total' => $totalTransactions,
                'completed' => $completedCount,
                'failed' => $failedCount,
                'success_rate' => $totalTransactions > 0
                    ? round(($completedCount / $totalTransactions) * 100, 1)
                    : 0.0,
                'cash' => (int) ($paymentBreakdown['cash'] ?? 0),
                'card' => (int) ($paymentBreakdown['card'] ?? 0),
                'other' => (int) ($paymentBreakdown['other'] ?? 0),
            ],
            'errors' => [
                'failed_transactions' => $failedCount,
                'active_alarms' => $activeAlarms,
                'fault_slots' => $slotsFault,
            ],
            'forecast' => [
                'daily_avg_revenue' => round($dailyAvgRevenue, 2),
                'daily_avg_orders' => round($dailyAvgOrders, 1),
                'next_30_days_revenue' => round($dailyAvgRevenue * 30, 2),
                'next_30_days_orders' => (int) round($dailyAvgOrders * 30),
            ],
            'pnl' => [
                'gross_revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'gross_profit' => round($grossProfit, 2),
                'gross_margin_percent' => $marginPercent,
                'gross_margin_label' => $this->grossMarginLabel($revenue, $marginPercent),
            ],
            'operations' => [
                'machines_online' => $machinesOnline,
                'machines_total' => count($machineIds),
                'slots_total' => $slotsTotal,
                'slots_in_stock' => $slotsInStock,
                'slots_low_stock' => $slotsLow,
                'slots_empty' => $slotsEmpty,
                'slots_fault' => $slotsFault,
                'fill_rate_percent' => $slotsTotal > 0
                    ? round(($slotsInStock / $slotsTotal) * 100, 1)
                    : 0.0,
            ],
        ];
    }

    /**
     * @param  list<string>  $machineNumbers
     */
    private function sumCompletedOrderRevenue(
        array $machineNumbers,
        CarbonInterface $from,
        CarbonInterface $to,
    ): float {
        if ($machineNumbers === []) {
            return 0.0;
        }

        return (float) DB::table('orders')
            ->leftJoin('machine_slots', 'orders.machine_slot_id', '=', 'machine_slots.id')
            ->leftJoin('products', 'machine_slots.product_id', '=', 'products.id')
            ->whereIn('orders.machine_no', $machineNumbers)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$from, $to])
            ->sum(DB::raw(
                'CASE WHEN orders.prize_amount > 0 THEN orders.prize_amount ELSE COALESCE(machine_slots.price, products.price, 0) END'
            ));
    }

    private function grossMarginLabel(float $revenue, float $marginPercent): string
    {
        if ($revenue <= 0.0) {
            return 'N/A';
        }

        if ($marginPercent > 999.0) {
            return '>999%';
        }

        if ($marginPercent < -999.0) {
            return '<-999%';
        }

        return number_format($marginPercent, 1).'%';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMetrics(): array
    {
        return [
            'sales' => ['revenue' => 0.0, 'orders' => 0, 'avg_order_value' => 0.0],
            'costs' => ['cogs' => 0.0, 'gross_profit' => 0.0, 'margin_percent' => 0.0],
            'transactions' => [
                'total' => 0, 'completed' => 0, 'failed' => 0,
                'success_rate' => 0.0, 'cash' => 0, 'card' => 0, 'other' => 0,
            ],
            'errors' => ['failed_transactions' => 0, 'active_alarms' => 0, 'fault_slots' => 0],
            'forecast' => [
                'daily_avg_revenue' => 0.0, 'daily_avg_orders' => 0.0,
                'next_30_days_revenue' => 0.0, 'next_30_days_orders' => 0,
            ],
            'pnl' => [
                'gross_revenue' => 0.0, 'cogs' => 0.0,
                'gross_profit' => 0.0, 'gross_margin_percent' => 0.0,
                'gross_margin_label' => 'N/A',
            ],
            'operations' => [
                'machines_online' => 0, 'machines_total' => 0,
                'slots_total' => 0, 'slots_in_stock' => 0,
                'slots_low_stock' => 0, 'slots_empty' => 0,
                'slots_fault' => 0, 'fill_rate_percent' => 0.0,
            ],
        ];
    }
}

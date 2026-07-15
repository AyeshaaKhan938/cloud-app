<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Machine;
use App\Models\MachineAlarm;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\ProductLotteryCode;
use App\Services\Users\UserCloudScope;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Live KPIs for the main dashboard — all values from the real database.
 */
final class DashboardBusinessStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -45;

    protected ?string $heading = 'Key metrics';

    protected ?string $description = 'Live fleet health, revenue, and stock alerts';

    protected function getStats(): array
    {
        $scope = app(UserCloudScope::class);
        $machineQuery = $scope->scopeMachines(Machine::query());
        $machineIds = $scope->ownedMachineIds();
        $machineNumbers = $scope->ownedMachineNumbers();

        $totalMachines = (clone $machineQuery)->count();
        $activeMachines = (clone $machineQuery)->where('is_enabled', true)->count();
        $offlineCount = $totalMachines - $activeMachines;

        $orderQuery = $scope->scopeOrders(Order::query()->completed());

        $drawsQuery = ProductLotteryCode::query();
        if (! $scope->hasFullCloudAccess()) {
            if ($machineNumbers === []) {
                $drawsQuery->whereRaw('0 = 1');
            } else {
                $drawsQuery->whereIn('machine_no', $machineNumbers);
            }
        }

        $drawsToday = (clone $drawsQuery)->whereDate('redeemed_at', today())->count();
        $drawsLast7 = collect(range(6, 0))
            ->map(fn (int $d): int => (clone $drawsQuery)
                ->whereDate('redeemed_at', now()->subDays($d))
                ->count())
            ->values()
            ->toArray();

        $revenueToday = (float) (clone $orderQuery)->whereDate('completed_at', today())->sum('prize_amount');
        $revenueMonth = (float) (clone $orderQuery)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->sum('prize_amount');
        $revenueYear = (float) (clone $orderQuery)
            ->whereYear('completed_at', now()->year)
            ->sum('prize_amount');
        $revenueLast7 = collect(range(6, 0))
            ->map(fn (int $d): int => (int) (clone $orderQuery)
                ->whereDate('completed_at', now()->subDays($d))
                ->sum('prize_amount'))
            ->values()
            ->toArray();

        $alarmQuery = MachineAlarm::query()->whereNull('acknowledged_at');
        if (! $scope->hasFullCloudAccess()) {
            $alarmQuery->when(
                $machineIds === [],
                fn ($query) => $query->whereRaw('0 = 1'),
                fn ($query) => $query->whereIn('machine_id', $machineIds),
            );
        }
        $activeAlarms = $alarmQuery->count();

        $slotQuery = MachineSlot::query()
            ->where('is_active', true)
            ->whereNotNull('product_id');

        if (! $scope->hasFullCloudAccess()) {
            $slotQuery->when(
                $machineIds === [],
                fn ($query) => $query->whereRaw('0 = 1'),
                fn ($query) => $query->whereIn('machine_id', $machineIds),
            );
        }

        $lowStockSlots = (clone $slotQuery)
            ->where('is_fault', false)
            ->whereColumn('current_stock', '<=', 'stock_alarm_threshold')
            ->where('current_stock', '>', 0)
            ->count();

        $outOfStockSlots = (clone $slotQuery)
            ->where('current_stock', 0)
            ->count();

        $fillAlerts = $lowStockSlots + $outOfStockSlots;

        return [
            Stat::make('Active Machines', "{$activeMachines} / {$totalMachines}")
                ->description($offlineCount > 0 ? "{$offlineCount} currently offline" : 'All machines online')
                ->descriptionIcon(Heroicon::OutlinedCpuChip)
                ->color($offlineCount === 0 ? 'success' : 'warning'),

            Stat::make('Revenue Today', '$'.number_format($revenueToday, 2))
                ->description("{$drawsToday} ".($drawsToday === 1 ? 'transaction' : 'transactions').' today')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->chart($revenueLast7)
                ->color('success'),

            Stat::make('Revenue This Month', '$'.number_format($revenueMonth, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('info'),

            Stat::make('Revenue This Year', '$'.number_format($revenueYear, 2))
                ->description(now()->format('Y').' cumulative')
                ->descriptionIcon(Heroicon::OutlinedChartBar)
                ->color('primary'),

            Stat::make('Lottery Draws Today', number_format($drawsToday))
                ->description('Codes redeemed today')
                ->descriptionIcon(Heroicon::OutlinedTicket)
                ->chart($drawsLast7)
                ->color('primary'),

            Stat::make('Active Alarms', (string) $activeAlarms)
                ->description($activeAlarms > 0 ? 'Unacknowledged machine alerts' : 'No open alerts')
                ->descriptionIcon(Heroicon::OutlinedBellAlert)
                ->color($activeAlarms === 0 ? 'success' : 'danger'),

            Stat::make('Fill Alerts', (string) $fillAlerts)
                ->description("{$outOfStockSlots} empty · {$lowStockSlots} low stock")
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color($fillAlerts === 0 ? 'success' : ($outOfStockSlots > 0 ? 'danger' : 'warning')),
        ];
    }
}

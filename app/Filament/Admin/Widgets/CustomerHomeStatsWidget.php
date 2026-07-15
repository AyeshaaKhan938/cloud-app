<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\WorkOrderStatus;
use App\Models\Machine;
use App\Models\MachineAlarm;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\ProductLotteryCode;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Users\UserCloudScope;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CustomerHomeStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -50;

    protected ?string $heading = 'Your overview';

    protected ?string $description = 'Machines, sales, tickets, and stock at a glance';

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
        $revenueToday = (float) (clone $orderQuery)->whereDate('completed_at', today())->sum('prize_amount');
        $revenueMonth = (float) (clone $orderQuery)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->sum('prize_amount');

        $revenueLast7 = collect(range(6, 0))
            ->map(fn (int $d): int => (int) (clone $orderQuery)
                ->whereDate('completed_at', now()->subDays($d))
                ->sum('prize_amount'))
            ->values()
            ->toArray();

        $drawsToday = $machineNumbers === []
            ? 0
            : ProductLotteryCode::query()
                ->whereIn('machine_no', $machineNumbers)
                ->whereDate('redeemed_at', today())
                ->count();

        $activeAlarms = $machineIds === []
            ? 0
            : MachineAlarm::query()
                ->whereIn('machine_id', $machineIds)
                ->whereNull('acknowledged_at')
                ->count();

        $slotQuery = MachineSlot::query()
            ->where('is_active', true)
            ->whereNotNull('product_id');

        if ($machineIds !== []) {
            $slotQuery->whereIn('machine_id', $machineIds);
        } else {
            $slotQuery->whereRaw('0 = 1');
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

        $user = auth()->user();
        $openTickets = $user instanceof User
            ? WorkOrder::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [WorkOrderStatus::Unprocessed, WorkOrderStatus::Processing])
                ->count()
            : 0;

        return [
            Stat::make('Your machines', "{$activeMachines} / {$totalMachines}")
                ->description($offlineCount > 0 ? "{$offlineCount} offline" : ($totalMachines > 0 ? 'All online' : 'No machines linked yet'))
                ->descriptionIcon(Heroicon::OutlinedCpuChip)
                ->color($totalMachines === 0 ? 'gray' : ($offlineCount === 0 ? 'success' : 'warning')),

            Stat::make('Revenue today', '$'.number_format($revenueToday, 2))
                ->description(now()->format('M j, Y'))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->chart($revenueLast7)
                ->color('success'),

            Stat::make('Revenue this month', '$'.number_format($revenueMonth, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('info'),

            Stat::make('Open support tickets', (string) $openTickets)
                ->description($openTickets > 0 ? 'Awaiting response' : 'No open tickets')
                ->descriptionIcon(Heroicon::OutlinedLifebuoy)
                ->color($openTickets > 0 ? 'warning' : 'success'),

            Stat::make('Lottery draws today', number_format($drawsToday))
                ->description('Codes redeemed today')
                ->descriptionIcon(Heroicon::OutlinedTicket)
                ->color('primary'),

            Stat::make('Fill alerts', (string) $fillAlerts)
                ->description("{$outOfStockSlots} empty · {$lowStockSlots} low stock")
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color($fillAlerts === 0 ? 'success' : ($outOfStockSlots > 0 ? 'danger' : 'warning')),

            Stat::make('Active alarms', (string) $activeAlarms)
                ->description($activeAlarms > 0 ? 'Unacknowledged alerts' : 'No open alerts')
                ->descriptionIcon(Heroicon::OutlinedBellAlert)
                ->color($activeAlarms === 0 ? 'success' : 'danger'),
        ];
    }
}

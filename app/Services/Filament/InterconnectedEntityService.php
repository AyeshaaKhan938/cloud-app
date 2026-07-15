<?php

declare(strict_types=1);

namespace App\Services\Filament;

use App\Filament\Admin\Resources\Machines\MachineResource;
use App\Filament\Admin\Resources\Machines\Pages\ViewMachine;
use App\Filament\Admin\Resources\MyWorkOrders\MyWorkOrderResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Products\Pages\ViewProduct;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Admin\Resources\WorkOrders\WorkOrderResource;
use App\Models\Machine;
use App\Models\MachineAlarm;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductLottery;
use App\Models\WorkOrder;
use App\Services\Support\WorkOrderService;
use App\Services\Users\UserCloudScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class InterconnectedEntityService
{
    /**
     * @return array{
     *     total: int,
     *     stocked: int,
     *     empty: int,
     *     low_stock: int,
     *     fault: int,
     *     unassigned: int
     * }
     */
    public function machineSlotSummary(Machine $machine): array
    {
        $slots = $machine->slots()->with('product')->get();

        $stocked = 0;
        $empty = 0;
        $lowStock = 0;
        $fault = 0;
        $unassigned = 0;

        foreach ($slots as $slot) {
            if ($slot->is_fault) {
                $fault++;
            }

            if ($slot->product_id === null) {
                $unassigned++;

                continue;
            }

            if ($slot->current_stock === 0) {
                $empty++;
            } elseif ($slot->isLowStock()) {
                $lowStock++;
            } else {
                $stocked++;
            }
        }

        return [
            'total' => $slots->count(),
            'stocked' => $stocked,
            'empty' => $empty,
            'low_stock' => $lowStock,
            'fault' => $fault,
            'unassigned' => $unassigned,
        ];
    }

    /**
     * @return Collection<int, MachineSlot>
     */
    public function machineSlots(Machine $machine): Collection
    {
        return $machine->slots()
            ->with('product')
            ->orderBy('line_number')
            ->get();
    }

    /**
     * @return array{
     *     orders: Collection<int, Order>,
     *     alarms: Collection<int, MachineAlarm>,
     *     tickets: Collection<int, WorkOrder>
     * }
     */
    public function machineRelatedRecords(Machine $machine): array
    {
        $machineNumber = $machine->machine_number;

        $orders = app(UserCloudScope::class)
            ->scopeOrders(Order::query())
            ->where('machine_no', $machineNumber)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $alarms = MachineAlarm::query()
            ->where('machine_id', $machine->id)
            ->latest('triggered_at')
            ->limit(10)
            ->get();

        $tickets = WorkOrder::query()
            ->where(function (Builder $query) use ($machine): void {
                $query
                    ->where('machine_id', $machine->id)
                    ->orWhere('device_number', $machine->machine_number);
            })
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return [
            'orders' => $orders,
            'alarms' => $alarms,
            'tickets' => $tickets,
        ];
    }

    /**
     * @return Collection<int, MachineSlot>
     */
    public function productMachineDeployments(Product $product): Collection
    {
        return MachineSlot::query()
            ->where('product_id', $product->id)
            ->with(['machine', 'product'])
            ->whereHas('machine', fn (Builder $query): Builder => app(UserCloudScope::class)->scopeMachines($query))
            ->orderBy('machine_id')
            ->orderBy('line_number')
            ->get();
    }

    /**
     * @return Collection<int, ProductLottery>
     */
    public function productLotteries(Product $product): Collection
    {
        return $product->productLotteries()
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
    }

    public function findMachineByNumber(?string $machineNumber): ?Machine
    {
        if ($machineNumber === null || $machineNumber === '') {
            return null;
        }

        return app(UserCloudScope::class)
            ->scopeMachines(Machine::query())
            ->where('machine_number', $machineNumber)
            ->first();
    }

    public function machineViewUrl(Machine $machine, string $tab = 'overview'): string
    {
        $url = ViewMachine::getUrl(['record' => $machine]);

        return $tab === 'overview' ? $url : $url.'?tab='.$tab;
    }

    public function productViewUrl(Product $product, string $tab = 'overview'): string
    {
        $url = ViewProduct::getUrl(['record' => $product]);

        return $tab === 'overview' ? $url : $url.'?tab='.$tab;
    }

    public function orderViewUrl(Order $order): string
    {
        return OrderResource::getUrl(parameters: [
            'tableAction' => 'view',
            'tableActionRecord' => $order,
        ]);
    }

    public function supportTicketViewUrl(WorkOrder $ticket): string
    {
        if (app(WorkOrderService::class)->canManageQueue()) {
            return WorkOrderResource::getUrl('view', ['record' => $ticket]);
        }

        return MyWorkOrderResource::getUrl('view', ['record' => $ticket]);
    }

    public function machineListUrl(): string
    {
        return MachineResource::getUrl();
    }

    public function productListUrl(): string
    {
        return ProductResource::getUrl();
    }
}

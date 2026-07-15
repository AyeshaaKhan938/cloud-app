<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Order;
use App\Models\ProductLottery;
use App\Services\Kiosk\KioskOperatorAlertService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Endpoints de administración para una máquina específica.
 *
 * Requiere middleware lottery.management (Bearer token).
 */
final class AdminMachineController extends Controller
{
    /**
     * GET /api/v1/admin/machines/{machineNo}/dashboard
     *
     * Resumen operacional del día: ventas, stock bajo, slots faulty y lotteries activas.
     */
    public function dashboard(string $machineNo, KioskOperatorAlertService $alertService): JsonResponse
    {
        $machine = $this->resolveMachine($machineNo);

        $today = now()->startOfDay();

        $ordersToday = Order::query()
            ->forMachine($machineNo)
            ->whereDate('created_at', today())
            ->get();

        $slots = $machine->slots()->with('product')->get();

        $activeLotteries = ProductLottery::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('machine_no')->orWhere('machine_no', request()->route('machineNo'));
            })
            ->count();

        return response()->json([
            'machine' => [
                'machine_number' => $machine->machine_number,
                'machine_name' => $machine->machine_name,
                'is_enabled' => $machine->is_enabled,
                'detailed_address' => $machine->detailed_address,
                'service_hot_line' => $machine->service_hot_line,
            ],
            'today' => [
                'orders_completed' => $ordersToday->where('status', 'completed')->count(),
                'orders_failed' => $ordersToday->where('status', 'failed')->count(),
                'revenue' => number_format(
                    (float) $ordersToday->where('status', 'completed')->sum('prize_amount'),
                    2, '.', ''
                ),
            ],
            'inventory' => [
                'total_slots' => $slots->count(),
                'active_slots' => $slots->where('is_active', true)->count(),
                'low_stock_count' => $slots->filter(fn ($s) => $s->isLowStock())->count(),
                'fault_count' => $slots->where('is_fault', true)->count(),
                'empty_count' => $slots->whereNull('product_id')->count(),
            ],
            'active_lotteries' => $activeLotteries,
            'operator_alerts' => $alertService->alertsFor($machine),
        ]);
    }

    /**
     * GET /api/v1/admin/machines/{machineNo}/slots
     *
     * Todos los slots (incluyendo inactivos y sin producto) con vista completa de inventario.
     */
    public function slots(string $machineNo): JsonResponse
    {
        $machine = $this->resolveMachine($machineNo);

        $slots = $machine->slots()
            ->with('product')
            ->orderBy('line_number')
            ->get();

        return response()->json([
            'machine_number' => $machine->machine_number,
            'machine_name' => $machine->machine_name,
            'slots' => $slots->map(fn ($slot) => [
                'id' => $slot->id,
                'line_number' => $slot->line_number,
                'product_id' => $slot->product_id,
                'product_name' => $slot->product?->name,
                'product_sku' => $slot->product?->sku,
                'product_image' => $slot->product?->main_image,
                'price' => (string) $slot->price,
                'current_stock' => $slot->current_stock,
                'max_stock' => $slot->max_stock,
                'stock_alarm_threshold' => $slot->stock_alarm_threshold,
                'is_active' => $slot->is_active,
                'is_fault' => $slot->is_fault,
                'is_available' => $slot->isAvailable(),
                'is_low_stock' => $slot->isLowStock(),
            ]),
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function resolveMachine(string $machineNo): Machine
    {
        $machine = Machine::query()
            ->where('machine_number', $machineNo)
            ->first();

        if ($machine === null) {
            throw new NotFoundHttpException("Machine '{$machineNo}' not found.");
        }

        return $machine;
    }
}

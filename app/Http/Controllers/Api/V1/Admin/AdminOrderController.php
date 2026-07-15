<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historial de transacciones de una máquina para el panel admin del kiosk.
 *
 * Requiere middleware lottery.management (Bearer token).
 */
final class AdminOrderController extends Controller
{
    /**
     * GET /api/v1/admin/machines/{machineNo}/orders
     *
     * Devuelve órdenes paginadas para una máquina, ordenadas por más reciente.
     *
     * Query params:
     *   - per_page  int  (1-100, default 20)
     *   - page      int
     *   - status    string  completed|failed
     *   - date      string  YYYY-MM-DD  (filtra por día)
     */
    public function index(Request $request, string $machineNo): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:completed,failed'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $query = Order::query()
            ->forMachine($machineNo)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $paginated = $query->paginate(
            perPage: (int) $request->input('per_page', 20)
        );

        return response()->json([
            'data' => collect($paginated->items())->map(fn (Order $order) => [
                'id' => $order->id,
                'product_name' => $order->product_name,
                'prize_name' => $order->prize_name,
                'prize_amount' => (string) $order->prize_amount,
                'payment_method' => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'line_number' => $order->line_number,
                'status' => $order->status,
                'notes' => $order->notes,
                'completed_at' => $order->completed_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }
}

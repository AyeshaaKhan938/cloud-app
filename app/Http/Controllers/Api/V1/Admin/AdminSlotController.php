<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MachineSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gestión de slots individuales desde el kiosk admin.
 *
 * Requiere middleware lottery.management (Bearer token).
 */
final class AdminSlotController extends Controller
{
    /**
     * PATCH /api/v1/admin/slots/{id}
     *
     * Actualiza stock, producto asignado, precio, estado activo o faulted.
     * Solo actualiza los campos que vienen en el body (patch parcial).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $slot = MachineSlot::find($id);

        if ($slot === null) {
            throw new NotFoundHttpException("Slot #{$id} not found.");
        }

        $validated = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'integer', 'exists:products,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'current_stock' => ['sometimes', 'integer', 'min:0'],
            'max_stock' => ['sometimes', 'integer', 'min:1'],
            'stock_alarm_threshold' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_fault' => ['sometimes', 'boolean'],
        ]);

        $slot->fill($validated);
        $slot->save();

        $slot->load('product');

        return response()->json([
            'ok' => true,
            'slot' => [
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
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Expone el inventario de slots de una máquina al kiosk Flutter.
 *
 * La Flutter app llama este endpoint al arrancar para saber qué productos
 * están disponibles, sus precios y qué slots tienen stock.
 */
final class MachineSlotController extends Controller
{
    /**
     * GET /api/v1/machines/{machineNo}/slots
     *
     * Devuelve todos los slots activos de la máquina con su producto e inventario.
     * Solo incluye slots con producto asignado; excluye vacíos (product_id = null).
     */
    public function index(string $machineNo): JsonResponse
    {
        $machine = Machine::query()
            ->where('machine_number', $machineNo)
            ->where('is_enabled', true)
            ->first();

        if ($machine === null) {
            throw new NotFoundHttpException("Machine '{$machineNo}' not found or disabled.");
        }

        $machine->touchLastSeen();

        $slots = $machine->slots()
            ->with('product')
            ->where('is_active', true)
            ->whereNotNull('product_id')
            ->orderBy('line_number')
            ->get();

        return response()->json([
            'machine_number' => $machine->machine_number,
            'machine_name' => $machine->machine_name,
            'age_verification_enabled' => $machine->age_verification_enabled,
            'minimum_age' => $machine->minimum_age ?? (int) config('age_verification.min_age', 18),
            'slots' => $slots->map(function ($slot) use ($machine) {
                $product = $slot->product;
                $requiresAgeVerification = $product !== null
                    && $machine->age_verification_enabled
                    && $product->requires_age_verification;

                return [
                    'line_number' => $slot->line_number,
                    'product_id' => $slot->product_id,
                    'product_name' => $product?->name,
                    'product_image' => $product?->main_image,
                    'product_description' => $product?->description,
                    'product_brand' => $product?->brand,
                    'product_media' => $product?->media_expansions ?? [],
                    'requires_age_verification' => $requiresAgeVerification,
                    'minimum_age' => $requiresAgeVerification
                        ? ($product->minimum_age ?? $machine->minimum_age ?? (int) config('age_verification.min_age', 18))
                        : null,
                    'price' => (float) $slot->price,
                    'current_stock' => $slot->current_stock,
                    'max_stock' => $slot->max_stock,
                    'is_available' => $slot->isAvailable(),
                    'is_fault' => $slot->is_fault,
                ];
            }),
        ]);
    }
}

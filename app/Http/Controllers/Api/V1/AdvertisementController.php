<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\AdvertisementGroupSlot;
use App\Models\Advertisement;
use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

final class AdvertisementController
{
    /**
     * GET /api/v1/machines/{machineNo}/advertisements
     *
     * Devuelve los anuncios organizados por slot para el kiosk Flutter.
     *
     * Resolución del grupo:
     *   1. advertisement_group asignado directamente a la máquina
     *   2. advertisement_group del MachineGroup al que pertenece la máquina
     *   3. Sin grupo → responde con slots vacíos (no error)
     *
     * Response shape:
     * {
     *   "group_id": 1,
     *   "group_name": "Downtown",
     *   "slots": {
     *     "screensaver":     [ { id, title, type, media_url, link_url, sort_order }, ... ],
     *     "top":             [ ... ],
     *     "external_screen": [ ... ]
     *   }
     * }
     */
    public function index(string $machineNo): JsonResponse
    {
        // Buscar máquina activa
        $machine = Machine::query()
            ->where('machine_number', $machineNo)
            ->where('is_enabled', true)
            ->with(['advertisementGroup', 'machineGroup.advertisementGroup'])
            ->first();

        if (! $machine) {
            return response()->json(['message' => 'Machine not found.'], 404);
        }

        $machine->touchLastSeen();

        // Resolver grupo con prioridad: máquina individual → grupo → null
        $group = $machine->resolveAdvertisementGroup();

        if (! $group) {
            return response()->json([
                'group_id' => null,
                'group_name' => null,
                'slots' => [
                    'screensaver' => [],
                    'top' => [],
                    'external_screen' => [],
                ],
            ]);
        }

        // Construir respuesta por slot
        $slots = [];

        foreach (AdvertisementGroupSlot::cases() as $slot) {
            $ads = $group->advertisements()
                ->wherePivot('slot', $slot->value)
                ->orderByPivot('sort_order')
                ->get()
                ->map(fn (Advertisement $ad): array => [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'type' => $ad->type->value,          // 'image' | 'video'
                    'media_url' => Storage::disk('public')->url($ad->media_path),
                    'link_url' => $ad->link_url,
                    'sort_order' => $ad->pivot->sort_order,
                ])
                ->values()
                ->all();

            $slots[$slot->value] = $ads;
        }

        return response()->json([
            'group_id' => $group->id,
            'group_name' => $group->name,
            'slots' => $slots,
        ]);
    }
}

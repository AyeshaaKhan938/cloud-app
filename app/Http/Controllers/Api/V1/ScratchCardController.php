<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LotterySetting;
use App\Models\MachineSlot;
use App\Models\TenpointRedemption;
use App\Services\Lottery\TenpointMediaClient;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Scratch-card redemption flow backed by Ten Point Media validation.
 *
 *   1. Kiosk POSTs the code to /api/v1/scratch-card/redeem
 *   2. We check our local table — already redeemed? → 422 ALREADY_REDEEMED
 *   3. We call Ten Point Media — invalid? → 422 INVALID. Service down? → 503.
 *   4. We INSERT into tenpoint_redemptions (locks the code).
 *   5. We return per-tier slot lists. The kiosk:
 *        a) rolls weighted dice (Tier A weight vs Tier B weight) to pick a tier
 *        b) picks a random slot from the chosen tier (only slots with stock)
 *        c) dispenses + calls /scratch-card/confirm with the outcome.
 */
final class ScratchCardController extends Controller
{
    public function redeem(Request $request, TenpointMediaClient $tenpoint): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:64'],
            'machine_no' => ['required', 'string', 'max:64'],
        ]);

        $code = strtoupper(trim($data['code']));
        $machineNo = $data['machine_no'];

        // Idempotency check — see top-of-class comment for why this is unconditional.
        if (TenpointRedemption::query()->where('code', $code)->exists()) {
            return response()->json([
                'error' => 'ALREADY_REDEEMED',
                'message' => 'This code has already been used.',
            ], 422);
        }

        try {
            $valid = $tenpoint->validate($code);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => 'SERVICE_UNAVAILABLE',
                'message' => 'Validation service unavailable. Please try again later.',
            ], 503);
        }

        if (! $valid) {
            return response()->json([
                'error' => 'INVALID',
                'message' => 'Sorry, try again next time.',
            ], 422);
        }

        // Build the tier payload BEFORE we lock the code — if neither tier has
        // any in-stock slots assigned, refuse the redemption (don't burn the code).
        $tiers = $this->buildTierPayload($machineNo);

        $tierAHasSlots = ! empty($tiers['A']['slots']);
        $tierBHasSlots = ! empty($tiers['B']['slots']);
        if (! $tierAHasSlots && ! $tierBHasSlots) {
            return response()->json([
                'error' => 'NO_STOCK',
                'message' => 'Sorry, this machine has no lottery prizes available right now.',
            ], 503);
        }

        // Lock the code with a race-safe insert.
        try {
            DB::transaction(function () use ($code, $machineNo): void {
                TenpointRedemption::query()->create([
                    'code' => $code,
                    'machine_no' => $machineNo,
                    'validated_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            return response()->json([
                'error' => 'ALREADY_REDEEMED',
                'message' => 'This code has already been used.',
            ], 422);
        }

        return response()->json([
            'code' => $code,
            'tiers' => $tiers,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:64'],
            'tier' => ['required', 'string', 'max:8'],
            'line_number' => ['required', 'integer', 'min:1', 'max:255'],
            'prize_amount' => ['nullable', 'numeric', 'min:0'],
            'success' => ['required', 'boolean'],
            'error' => ['nullable', 'string', 'max:500'],
        ]);

        $code = strtoupper(trim($data['code']));

        $redemption = TenpointRedemption::query()->where('code', $code)->first();

        if ($redemption === null) {
            return response()->json([
                'error' => 'NOT_FOUND',
                'message' => 'Redemption record not found.',
            ], 404);
        }

        $redemption->update([
            'tier' => $data['tier'],
            'line_number' => $data['line_number'],
            'prize_amount' => $data['prize_amount'] ?? 0,
            'dispense_success' => $data['success'],
            'dispensed_at' => now(),
            'dispense_error' => $data['error'] ?? null,
        ]);

        // Decrement stock on the chosen slot if dispense succeeded.
        if ($data['success']) {
            MachineSlot::query()
                ->whereHas('machine', fn ($q) => $q->where('machine_number', $redemption->machine_no))
                ->where('line_number', $data['line_number'])
                ->where('current_stock', '>', 0)
                ->decrement('current_stock');
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Build per-tier slot list for the kiosk's client-side dice roll.
     *
     * @return array{
     *   A: array{name: string, weight: int, slots: array<int, array{line_number: int, product_name: ?string}>},
     *   B: array{name: string, weight: int, slots: array<int, array{line_number: int, product_name: ?string}>}
     * }
     */
    private function buildTierPayload(string $machineNo): array
    {
        $settings = LotterySetting::current();

        $slotsForTier = function (string $tier) use ($machineNo): array {
            return MachineSlot::query()
                ->whereHas('machine', fn ($q) => $q->where('machine_number', $machineNo))
                ->where('lottery_tier', $tier)
                ->where('is_active', true)
                ->where('is_fault', false)
                ->where('current_stock', '>', 0)
                ->whereNotNull('product_id')
                ->with('product:id,name')
                ->orderBy('line_number')
                ->get()
                ->map(fn (MachineSlot $s): array => [
                    'line_number' => $s->line_number,
                    'product_name' => $s->product?->name,
                ])
                ->all();
        };

        return [
            'A' => [
                'name' => $settings->tier_a_name,
                'weight' => $settings->tier_a_weight,
                'slots' => $slotsForTier('A'),
            ],
            'B' => [
                'name' => $settings->tier_b_name,
                'weight' => $settings->tier_b_weight,
                'slots' => $slotsForTier('B'),
            ],
        ];
    }
}

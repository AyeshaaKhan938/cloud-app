<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Http\Resources\Api\ProductLotteryCodeLookupResource;
use App\Models\Machine;
use App\Models\ProductLotteryCode;
use App\Services\AgeVerification\AgeVerificationRequirement;
use App\Support\VendingSlotLayout;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Builds kiosk-compatible lookup JSON: always HTTP 200 with a {@code data} array.
 *
 * @see ProductLotteryCodeLookupService
 */
final class ProductLotteryKioskLookupResponder
{
    public function __construct(
        private readonly ProductLotteryCodeLookupService $lookupService,
        private readonly AgeVerificationRequirement $ageVerificationRequirement,
    ) {}

    public function respond(string $code): JsonResponse
    {
        try {
            $record = $this->lookupService->resolve($code);

            if ($record->isRedeemed()) {
                return $this->deny(
                    message: 'You are using the same coupon code again. Please try a different coupon.',
                    idempotent: true,
                    record: $record,
                );
            }

            return response()->json([
                'data' => [$this->successPayload($record)],
            ]);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 404) {
                return response()->json(['data' => []]);
            }

            return $this->deny(
                message: $exception->getMessage() !== '' ? $exception->getMessage() : 'Invalid code.',
                idempotent: false,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function successPayload(ProductLotteryCode $record): array
    {
        $record->loadMissing(['prize', 'productLottery.product']);
        $prize = $record->prize;
        $lottery = $record->productLottery;
        $tierLabel = filled($prize?->name) ? $prize->name : ($prize?->tier_code ?? '');
        $amount = number_format((float) ($prize?->prize_amount ?? 0), 2);

        $resource = (new ProductLotteryCodeLookupResource($record))->resolve(request());

        $hardwareLine = $prize?->line_number !== null ? (int) $prize->line_number : null;

        $payload = array_merge($resource, [
            'canVend' => true,
            'idempotent' => false,
            'message' => 'You got '.$tierLabel.' — $'.$amount.' off!',
            'price_tier' => $prize?->tier_code ?? '',
            'prize_amount' => (string) ($prize?->prize_amount ?? '0'),
            'machine_no' => $lottery->machine_no ?? '',
            'client_number' => $hardwareLine !== null
                ? VendingSlotLayout::hardwareLineToClientNumber($hardwareLine)
                : null,
        ]);

        $machineNo = $lottery->machine_no ?? '';

        if ($machineNo !== '') {
            $machine = Machine::query()->where('machine_number', $machineNo)->first();
            $product = $machine !== null
                ? $this->ageVerificationRequirement->resolveProductForCode($record, $machine)
                : null;

            if ($machine !== null && $product !== null) {
                $payload = array_merge($payload, $this->ageVerificationRequirement->kioskPayload($machine, $product));
            } else {
                $payload['requires_age_verification'] = false;
                $payload['minimum_age'] = (int) config('age_verification.min_age', 18);
            }
        }

        return $payload;
    }

    private function deny(string $message, bool $idempotent, ?ProductLotteryCode $record = null): JsonResponse
    {
        $payload = [
            'canVend' => false,
            'idempotent' => $idempotent,
            'message' => $message,
        ];

        if ($record !== null) {
            $payload['code'] = $record->code;
        }

        return response()->json(['data' => [$payload]]);
    }
}

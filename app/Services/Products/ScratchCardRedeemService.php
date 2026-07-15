<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Http\Resources\Api\ProductLotteryCodeLookupResource;
use App\Models\Machine;
use App\Models\ProductLotteryCode;
use App\Services\AgeVerification\AgeVerificationRequirement;
use App\Services\AgeVerification\AgeVerificationSessionService;
use App\Support\VendingSlotLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ScratchCardRedeemService
{
    public function __construct(
        private readonly ProductLotteryCodeLookupService $lookupService,
        private readonly AgeVerificationSessionService $ageVerificationSessionService,
        private readonly AgeVerificationRequirement $ageVerificationRequirement,
    ) {}

    public function redeem(string $code, string $machineNo, ?string $ageVerificationSessionId): JsonResponse
    {
        $machine = Machine::query()
            ->where('machine_number', $machineNo)
            ->first();

        if ($machine === null) {
            throw ValidationException::withMessages([
                'machine_no' => 'Machine not found.',
            ]);
        }

        try {
            $record = $this->lookupService->resolve($code);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 404) {
                throw ValidationException::withMessages([
                    'code' => 'Code not found.',
                ]);
            }

            throw ValidationException::withMessages([
                'code' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Invalid code.',
            ]);
        }

        if ($record->isRedeemed()) {
            throw ValidationException::withMessages([
                'code' => 'You are using the same coupon code again. Please try a different coupon.',
            ]);
        }

        $product = $this->ageVerificationRequirement->resolveProductForCode($record, $machine);

        $requiresAgeVerification = $product !== null
            && $this->ageVerificationRequirement->isRequired($machine, $product);

        $this->ageVerificationSessionService->assertRedeemable(
            $ageVerificationSessionId,
            $machineNo,
            $requiresAgeVerification,
        );

        $record->update(['redeemed_at' => now()]);
        $record->refresh()->loadMissing(['prize', 'productLottery.product']);

        return response()->json([
            'data' => [$this->successPayload($record, $machine)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function successPayload(ProductLotteryCode $record, Machine $machine): array
    {
        $prize = $record->prize;
        $lottery = $record->productLottery;
        $tierLabel = filled($prize?->name) ? $prize->name : ($prize?->tier_code ?? '');
        $amount = number_format((float) ($prize?->prize_amount ?? 0), 2);
        $resource = (new ProductLotteryCodeLookupResource($record))->resolve(request());
        $hardwareLine = $prize?->line_number !== null ? (int) $prize->line_number : null;
        $product = $this->ageVerificationRequirement->resolveProductForCode($record, $machine);

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

        if ($product !== null) {
            $payload = array_merge($payload, $this->ageVerificationRequirement->kioskPayload($machine, $product));
        } else {
            $payload['requires_age_verification'] = false;
            $payload['minimum_age'] = (int) config('age_verification.min_age', 18);
        }

        return $payload;
    }
}

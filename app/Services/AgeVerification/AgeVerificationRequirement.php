<?php

declare(strict_types=1);

namespace App\Services\AgeVerification;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Product;
use App\Models\ProductLotteryCode;

final class AgeVerificationRequirement
{
    public function isRequired(Machine $machine, Product $product): bool
    {
        return $machine->age_verification_enabled
            && $product->requires_age_verification;
    }

    public function resolveMinimumAge(Machine $machine, Product $product): int
    {
        if ($product->minimum_age !== null) {
            return (int) $product->minimum_age;
        }

        if ($machine->minimum_age !== null) {
            return (int) $machine->minimum_age;
        }

        return (int) config('age_verification.min_age', 18);
    }

    /**
     * @return array{requires_age_verification: bool, minimum_age: int}
     */
    public function kioskPayload(Machine $machine, Product $product): array
    {
        return [
            'requires_age_verification' => $this->isRequired($machine, $product),
            'minimum_age' => $this->resolveMinimumAge($machine, $product),
        ];
    }

    public function resolveProductForCode(ProductLotteryCode $code, Machine $machine): ?Product
    {
        $code->loadMissing(['prize', 'productLottery.product']);

        $lineNumber = $code->prize?->line_number;

        if ($lineNumber !== null) {
            $slotProduct = MachineSlot::query()
                ->where('machine_id', $machine->id)
                ->where('line_number', $lineNumber)
                ->with('product')
                ->first()
                ?->product;

            if ($slotProduct !== null) {
                return $slotProduct;
            }
        }

        return $code->productLottery?->product;
    }
}

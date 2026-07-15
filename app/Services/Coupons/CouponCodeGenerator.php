<?php

declare(strict_types=1);

namespace App\Services\Coupons;

use App\Enums\CouponGenerationRule;
use App\Models\Coupon;
use App\Models\CouponCode;
use Illuminate\Support\Facades\DB;

final class CouponCodeGenerator
{
    /**
     * Generates codes when the distribution mode needs stored codes (only when none exist yet).
     */
    public function generateIfNeeded(Coupon $coupon): void
    {
        if (! $coupon->distribution_rule->requiresGeneratedCouponCodes()) {
            return;
        }

        if ($coupon->codes()->exists()) {
            return;
        }

        DB::transaction(function () use ($coupon): void {
            for ($i = 0; $i < $coupon->quantity; $i++) {
                CouponCode::query()->create([
                    'coupon_id' => $coupon->id,
                    'code' => $this->uniqueCode($coupon),
                    'times_used' => 0,
                    'max_uses' => $coupon->usage_frequency,
                ]);
            }
        });
    }

    private function uniqueCode(Coupon $coupon): string
    {
        return match ($coupon->generation_rule) {
            CouponGenerationRule::Numbers => $this->uniqueNumericCode(),
            CouponGenerationRule::Letter => $this->uniqueLetterCode(),
            CouponGenerationRule::LettersAndNumbers => $this->uniqueAlphanumericCode(),
        };
    }

    private function uniqueNumericCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (CouponCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function uniqueLetterCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= chr(random_int(65, 90));
            }
        } while (CouponCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function uniqueAlphanumericCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (CouponCode::query()->where('code', $code)->exists());

        return $code;
    }
}

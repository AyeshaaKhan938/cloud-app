<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Enums\CouponGenerationRule;
use App\Models\ProductLottery;
use App\Models\ProductLotteryCode;
use App\Models\ProductLotteryPrize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProductLotteryCodeGenerator
{
    public function __construct(
        private readonly WeightedLotteryPrizePicker $picker,
    ) {}

    /**
     * Creates one row per {@see ProductLottery::$quantity} with a unique code and a prize
     * picked using weighted random selection. Runs only when no codes exist yet.
     *
     * When quantity >= prize count, each active slot receives at least one code first so
     * all rows (client slots 1–36) are used before random top-up.
     */
    public function generateIfNeeded(ProductLottery $lottery): void
    {
        if ($lottery->codes()->exists()) {
            return;
        }

        $prizes = $lottery->prizes()->get();
        if ($prizes->isEmpty()) {
            return;
        }

        $totalWeight = $this->picker->totalWeight($prizes);
        if ($totalWeight < 1) {
            return;
        }

        DB::transaction(function () use ($lottery, $prizes, $totalWeight): void {
            $this->generateBalanced($lottery, $prizes, $totalWeight);
        });
    }

    /**
     * @param  Collection<int, ProductLotteryPrize>  $prizes
     */
    private function generateBalanced(ProductLottery $lottery, Collection $prizes, int $totalWeight): void
    {
        $prizeCount = $prizes->count();
        $quantity = max(0, (int) $lottery->quantity);

        if ($quantity === 0) {
            return;
        }

        $basePerPrize = $prizeCount > 0 ? intdiv($quantity, $prizeCount) : 0;
        $remainder = $prizeCount > 0 ? $quantity % $prizeCount : 0;

        if ($basePerPrize > 0) {
            foreach ($prizes as $prize) {
                for ($i = 0; $i < $basePerPrize; $i++) {
                    $this->createCode($lottery, $prize);
                }
            }
        }

        for ($i = 0; $i < $remainder; $i++) {
            $prize = $this->picker->pick($prizes, $totalWeight);
            $this->createCode($lottery, $prize);
        }
    }

    private function createCode(ProductLottery $lottery, ProductLotteryPrize $prize): void
    {
        ProductLotteryCode::query()->create([
            'product_lottery_id' => $lottery->id,
            'product_lottery_prize_id' => $prize->id,
            'code' => $this->uniqueCode($lottery),
        ]);
    }

    private function uniqueCode(ProductLottery $lottery): string
    {
        return match ($lottery->generation_rule) {
            CouponGenerationRule::Numbers => $this->uniqueNumericCode(),
            CouponGenerationRule::Letter => $this->uniqueLetterCode(),
            CouponGenerationRule::LettersAndNumbers => $this->uniqueAlphanumericCode(),
        };
    }

    private function uniqueNumericCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (ProductLotteryCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function uniqueLetterCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= chr(random_int(65, 90));
            }
        } while (ProductLotteryCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function uniqueAlphanumericCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (ProductLotteryCode::query()->where('code', $code)->exists());

        return $code;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\ProductLottery;
use App\Models\ProductLotteryCode;
use Illuminate\Support\Facades\DB;

final class ProductLotteryUnredeemedRedistributor
{
    public function __construct(
        private readonly WeightedLotteryPrizePicker $picker,
    ) {}

    /**
     * Re-assigns prize tiers for codes that are not yet redeemed, using current prizes and weights.
     */
    public function redistributeUnredeemed(ProductLottery $lottery): void
    {
        if (! $lottery->codes()->exists()) {
            return;
        }

        $prizes = $lottery->prizes()->orderBy('sort_order')->get();
        if ($prizes->isEmpty()) {
            return;
        }

        $totalWeight = $this->picker->totalWeight($prizes);
        if ($totalWeight < 1) {
            return;
        }

        DB::transaction(function () use ($lottery, $prizes, $totalWeight): void {
            $lottery->codes()
                ->whereNull('redeemed_at')
                ->orderBy('id')
                ->each(function (ProductLotteryCode $code) use ($prizes, $totalWeight): void {
                    $prize = $this->picker->pick($prizes, $totalWeight);
                    if ($code->product_lottery_prize_id !== $prize->id) {
                        $code->update(['product_lottery_prize_id' => $prize->id]);
                    }
                });
        });
    }
}

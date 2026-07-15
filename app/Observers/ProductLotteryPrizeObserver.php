<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ProductLottery;
use App\Models\ProductLotteryPrize;
use App\Services\Products\ProductLotteryUnredeemedRedistributor;
use Illuminate\Validation\ValidationException;

final class ProductLotteryPrizeObserver
{
    public function deleting(ProductLotteryPrize $prize): void
    {
        if ($prize->codes()->exists()) {
            throw ValidationException::withMessages([
                'data.prizes' => 'Cannot delete a prize tier that has lottery codes assigned. Redeemed codes keep their tier for history.',
            ]);
        }
    }

    public function saved(ProductLotteryPrize $prize): void
    {
        if ($prize->wasRecentlyCreated) {
            $this->redistributeForLottery($prize->product_lottery_id);

            return;
        }

        if ($prize->wasChanged('product_lottery_id')) {
            $original = $prize->getOriginal('product_lottery_id');
            if (is_numeric($original)) {
                $this->redistributeForLottery((int) $original);
            }
        }

        if ($prize->wasChanged(['weight', 'tier_code', 'sort_order', 'product_lottery_id'])) {
            $this->redistributeForLottery($prize->product_lottery_id);
        }
    }

    private function redistributeForLottery(int $lotteryId): void
    {
        $lottery = ProductLottery::query()->find($lotteryId);
        if ($lottery === null) {
            return;
        }

        app(ProductLotteryUnredeemedRedistributor::class)->redistributeUnredeemed($lottery->fresh());
    }
}

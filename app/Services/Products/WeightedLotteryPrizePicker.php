<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\ProductLotteryPrize;
use Illuminate\Support\Collection;

final class WeightedLotteryPrizePicker
{
    /**
     * @param  Collection<int, ProductLotteryPrize>  $prizes
     */
    public function totalWeight(Collection $prizes): int
    {
        return (int) $prizes->sum(fn (ProductLotteryPrize $p): int => max(1, (int) $p->weight));
    }

    /**
     * @param  Collection<int, ProductLotteryPrize>  $prizes
     */
    public function pick(Collection $prizes, ?int $totalWeight = null): ProductLotteryPrize
    {
        if ($prizes->isEmpty()) {
            throw new \InvalidArgumentException('Cannot pick from an empty prize collection.');
        }

        $totalWeight ??= $this->totalWeight($prizes);
        $roll = random_int(1, max(1, $totalWeight));
        $acc = 0;

        foreach ($prizes as $prize) {
            $acc += max(1, (int) $prize->weight);
            if ($roll <= $acc) {
                return $prize;
            }
        }

        /** @var ProductLotteryPrize $last */
        $last = $prizes->last();

        return $last;
    }
}

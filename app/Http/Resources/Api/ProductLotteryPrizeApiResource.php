<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\ProductLotteryPrize;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductLotteryPrize
 */
final class ProductLotteryPrizeApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductLotteryPrize $prize */
        $prize = $this->resource;

        return [
            'id' => $prize->id,
            'tier_code' => $prize->tier_code,
            'name' => $prize->name,
            'prize_amount' => (string) $prize->prize_amount,
            'weight' => $prize->weight,
            'sort_order' => $prize->sort_order,
        ];
    }
}

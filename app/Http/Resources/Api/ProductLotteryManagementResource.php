<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\ProductLottery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductLottery
 */
final class ProductLotteryManagementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductLottery $lottery */
        $lottery = $this->resource;

        return [
            'id' => $lottery->id,
            'public_draw_token' => $lottery->public_draw_token,
            'name' => $lottery->name,
            'is_active' => $lottery->is_active,
            'valid_from' => $lottery->valid_from?->toIso8601String(),
            'valid_until' => $lottery->valid_until?->toIso8601String(),
            'quantity' => $lottery->quantity,
            'generation_rule' => $lottery->generation_rule->value,
            'product' => [
                'id' => $lottery->product->id,
                'name' => $lottery->product->name,
                'sku' => $lottery->product->sku,
            ],
            'prizes_count' => (int) ($lottery->prizes_count ?? 0),
            'codes_count' => (int) ($lottery->codes_count ?? 0),
            'unredeemed_codes_count' => (int) ($lottery->unredeemed_codes_count ?? 0),
            'prizes' => ProductLotteryPrizeApiResource::collection($this->whenLoaded('prizes')),
        ];
    }
}

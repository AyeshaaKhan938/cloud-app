<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\ProductLotteryCode;
use App\Support\VendingSlotLayout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductLotteryCode
 */
final class ProductLotteryCodeLookupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductLotteryCode $code */
        $code = $this->resource;
        $prize = $code->prize;
        $lottery = $code->productLottery;
        $product = $lottery->product;

        return [
            'code' => $code->code,
            'redeemed' => $code->isRedeemed(),
            'redeemed_at' => $code->redeemed_at?->toIso8601String(),

            // Premio — incluye line_number para que Flutter sepa qué slot despachar
            'prize' => [
                'tier_code' => $prize->tier_code,
                'name' => $prize->name,
                'prize_amount' => (string) $prize->prize_amount,
                'line_number' => $prize->line_number,
                'client_number' => $prize->line_number !== null
                    ? VendingSlotLayout::hardwareLineToClientNumber((int) $prize->line_number)
                    : null,
            ],

            'currency' => 'USD',

            // Producto
            'product' => [
                'id' => $product?->id,
                'name' => $product?->name,
                'sku' => $product?->sku,
            ],

            // Lotería
            'lottery' => [
                'id' => $lottery->id,
                'name' => $lottery->name,
                'machine_no' => $lottery->machine_no ?? '',
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductLotteryPrizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_lottery_id',
    'tier_code',
    'name',
    'prize_amount',
    'weight',
    'sort_order',
    'line_number',
])]
final class ProductLotteryPrize extends Model
{
    /** @use HasFactory<ProductLotteryPrizeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prize_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ProductLottery, $this>
     */
    public function productLottery(): BelongsTo
    {
        return $this->belongsTo(ProductLottery::class);
    }

    /**
     * @return HasMany<ProductLotteryCode, $this>
     */
    public function codes(): HasMany
    {
        return $this->hasMany(ProductLotteryCode::class);
    }
}

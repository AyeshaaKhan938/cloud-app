<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductLotteryCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_lottery_id',
    'product_lottery_prize_id',
    'code',
    'redeemed_at',
    'dispense_status',
    'dispense_machine_no',
    'dispense_line',
    'dispense_error',
    'dispensed_at',
])]
final class ProductLotteryCode extends Model
{
    /** @use HasFactory<ProductLotteryCodeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
            'dispensed_at' => 'datetime',
            'dispense_line' => 'integer',
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
     * @return BelongsTo<ProductLotteryPrize, $this>
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(ProductLotteryPrize::class, 'product_lottery_prize_id');
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }
}

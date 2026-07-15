<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CouponCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'coupon_id',
    'code',
    'times_used',
    'max_uses',
])]
final class CouponCode extends Model
{
    /** @use HasFactory<CouponCodeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function usageLabel(): string
    {
        return $this->times_used.'/'.$this->max_uses;
    }
}

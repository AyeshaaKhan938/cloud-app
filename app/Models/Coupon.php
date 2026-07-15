<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponDiscountType;
use App\Enums\CouponDistributionRule;
use App\Enums\CouponGenerationRule;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'purchase_amount',
    'coupon_type',
    'discount_value',
    'usage_frequency',
    'generation_rule',
    'distribution_rule',
    'valid_from',
    'valid_until',
    'quantity',
])]
final class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'coupon_type' => CouponDiscountType::class,
            'generation_rule' => CouponGenerationRule::class,
            'distribution_rule' => CouponDistributionRule::class,
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<MachineGroup, $this>
     */
    public function machineGroups(): BelongsToMany
    {
        return $this->belongsToMany(MachineGroup::class);
    }

    /**
     * @return HasMany<CouponCode, $this>
     */
    public function codes(): HasMany
    {
        return $this->hasMany(CouponCode::class);
    }

    public function remainingCodesCount(): int
    {
        return $this->codes()
            ->whereColumn('times_used', '<', 'max_uses')
            ->count();
    }

    public function formattedDiscount(): string
    {
        if ($this->coupon_type === CouponDiscountType::Percentage) {
            $formatted = rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.');

            return ($formatted !== '' ? $formatted : '0').' %';
        }

        return '$'.number_format((float) $this->discount_value, 2, '.', ',');
    }
}

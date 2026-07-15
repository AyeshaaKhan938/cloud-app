<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponGenerationRule;
use Database\Factories\ProductLotteryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'product_id',
    'name',
    'is_active',
    'valid_from',
    'valid_until',
    'quantity',
    'generation_rule',
    'machine_no',
])]
final class ProductLottery extends Model
{
    /** @use HasFactory<ProductLotteryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (ProductLottery $lottery): void {
            if (blank($lottery->public_draw_token)) {
                $lottery->public_draw_token = static::newUniquePublicDrawToken();
            }
        });
    }

    public static function newUniquePublicDrawToken(): string
    {
        do {
            $token = Str::lower((string) Str::ulid());
        } while (self::query()->where('public_draw_token', $token)->exists());

        return $token;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'generation_rule' => CouponGenerationRule::class,
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductLotteryPrize, $this>
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(ProductLotteryPrize::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ProductLotteryCode, $this>
     */
    public function codes(): HasMany
    {
        return $this->hasMany(ProductLotteryCode::class);
    }

    public function remainingCodesCount(): int
    {
        return $this->codes()->whereNull('redeemed_at')->count();
    }
}

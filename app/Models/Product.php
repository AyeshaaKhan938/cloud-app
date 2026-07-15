<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'sku',
    'description',
    'barcode',
    'price',
    'cost',
    'is_active',
    'requires_age_verification',
    'minimum_age',
    'specification_id',
    'product_tag_id',
    'main_image',
    'product_icon',
    'paypal_currency',
    'brand',
    'product_number',
    'media_expansions',
    'product_tones',
    'model_3d_path',
    'product_remarks',
    'product_details',
])]
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
            'requires_age_verification' => 'boolean',
            'minimum_age' => 'integer',
            'media_expansions' => 'array',
            'product_tones' => 'array',
        ];
    }

    /**
     * Client account that owns this catalog entry (null = platform-wide product).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Product "category" row from the Categories screen ({@see Specification}).
     *
     * @return BelongsTo<Specification, $this>
     */
    public function specification(): BelongsTo
    {
        return $this->belongsTo(Specification::class);
    }

    /**
     * @return BelongsTo<ProductTag, $this>
     */
    public function productTag(): BelongsTo
    {
        return $this->belongsTo(ProductTag::class);
    }

    /**
     * @return HasMany<ProductLottery, $this>
     */
    public function productLotteries(): HasMany
    {
        return $this->hasMany(ProductLottery::class);
    }

    /**
     * Slots de máquinas donde este producto está asignado.
     *
     * @return HasMany<MachineSlot, $this>
     */
    public function machineSlots(): HasMany
    {
        return $this->hasMany(MachineSlot::class);
    }
}

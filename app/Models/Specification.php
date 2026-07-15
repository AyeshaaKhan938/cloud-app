<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpecificationSellingType;
use Database\Factories\SpecificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'specification_type',
    'value',
    'remarks',
])]
final class Specification extends Model
{
    /** @use HasFactory<SpecificationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specification_type' => SpecificationSellingType::class,
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

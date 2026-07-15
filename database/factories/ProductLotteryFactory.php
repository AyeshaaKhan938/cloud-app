<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponGenerationRule;
use App\Models\Product;
use App\Models\ProductLottery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLottery>
 */
final class ProductLotteryFactory extends Factory
{
    protected $model = ProductLottery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(3, true),
            'is_active' => true,
            'valid_from' => null,
            'valid_until' => null,
            'quantity' => fake()->numberBetween(2, 5),
            'generation_rule' => CouponGenerationRule::LettersAndNumbers,
        ];
    }
}

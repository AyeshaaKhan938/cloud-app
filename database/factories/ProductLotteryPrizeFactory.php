<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductLottery;
use App\Models\ProductLotteryPrize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLotteryPrize>
 */
final class ProductLotteryPrizeFactory extends Factory
{
    protected $model = ProductLotteryPrize::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_lottery_id' => ProductLottery::factory(),
            'tier_code' => fake()->randomElement(['A', 'B', 'C']),
            'name' => fake()->optional(0.7)->words(2, true),
            'prize_amount' => fake()->randomFloat(2, 1, 50),
            'weight' => fake()->numberBetween(1, 5),
            'sort_order' => 0,
        ];
    }
}

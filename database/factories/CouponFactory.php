<?php

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Enums\CouponDistributionRule;
use App\Enums\CouponGenerationRule;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CouponDiscountType::cases());

        return [
            'name' => fake()->words(3, true),
            'purchase_amount' => fake()->randomElement([0, 10, 25, 50]),
            'coupon_type' => $type,
            'discount_value' => $type === CouponDiscountType::Percentage
                ? fake()->numberBetween(5, 50)
                : fake()->randomFloat(2, 1, 20),
            'usage_frequency' => fake()->numberBetween(1, 100),
            'generation_rule' => CouponGenerationRule::Numbers,
            'distribution_rule' => CouponDistributionRule::CouponCode,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonths(3),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}

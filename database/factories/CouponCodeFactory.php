<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponCode>
 */
class CouponCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'code' => str_pad((string) fake()->unique()->numberBetween(0, 999_999), 6, '0', STR_PAD_LEFT),
            'times_used' => 0,
            'max_uses' => fake()->numberBetween(1, 50),
        ];
    }
}

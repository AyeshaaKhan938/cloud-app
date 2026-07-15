<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FinanceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceGroup>
 */
final class FinanceGroupFactory extends Factory
{
    protected $model = FinanceGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' finance',
            'finance_user_id' => User::factory(),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}

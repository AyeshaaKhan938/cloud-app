<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RenewalEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenewalEquipment>
 */
final class RenewalEquipmentFactory extends Factory
{
    protected $model = RenewalEquipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_name' => fake()->name().' device',
            'equipment_number' => fake()->unique()->numerify('##############'),
            'expires_at' => fake()->dateTimeBetween('now', '+2 years'),
            'yearly_renewal_amount' => fake()->randomElement([60.00, 120.00, 99.99]),
        ];
    }
}

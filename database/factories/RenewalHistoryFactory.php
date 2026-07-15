<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RenewalPayType;
use App\Enums\RenewalProgress;
use App\Models\RenewalHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenewalHistory>
 */
final class RenewalHistoryFactory extends Factory
{
    protected $model = RenewalHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_account' => 'sync@example.com',
            'user_name' => 'Sync User',
            'renewal_account' => fake()->optional()->userName(),
            'renewal_number' => 'REN-'.strtoupper(fake()->unique()->bothify('??######')),
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 20, 500),
            'renew_equipment' => fake()->sentence(),
            'renewal_schedule' => fake()->randomElement(['1 year', '2 years']),
            'renewal_progress' => RenewalProgress::Completed,
            'pay_type' => fake()->randomElement(RenewalPayType::cases()),
            'application_time' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (RenewalHistory $history): void {
            $history->loadMissing('user');
            if ($history->user !== null) {
                $history->user_account = (string) $history->user->email;
                $history->user_name = (string) $history->user->name;
                $history->saveQuietly();
            }
        });
    }
}

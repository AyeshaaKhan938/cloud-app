<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRegistrationMethod;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\CountrySelectOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'account' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => $email,
            'contact_emails' => $email,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->e164PhoneNumber(),
            'timezone' => 'UTC',
            'role' => fake()->randomElement(array_values(array_filter(
                UserRole::cases(),
                fn (UserRole $role): bool => $role !== UserRole::SubAccount,
            ))),
            'is_enabled' => true,
            'country' => fake()->boolean(40)
                ? fake()->randomElement(array_keys(CountrySelectOptions::all()))
                : null,
            'region' => fake()->optional()->city(),
            'registration_method' => UserRegistrationMethod::Email,
            'client_version' => fake()->optional()->numerify('1.#.#'),
            'wallet_balance' => 0,
            'wallet_excess_amount' => 0,
            'wallet_recharge_pending' => 0,
            'wallet_accumulated_recharge' => 0,
            'wallet_withdrawal_pending' => 0,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SuperAdmin,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Customer,
        ]);
    }

    public function subAccount(User $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SubAccount,
            'parent_user_id' => $parent->id,
            'created_by' => $parent->id,
        ]);
    }
}

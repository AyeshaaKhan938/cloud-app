<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductTag;
use App\Models\Specification;
use App\Support\PayPalCurrencyOptions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'description' => fake()->optional(0.6)->sentence(),
            'barcode' => fake()->boolean(50) ? fake()->unique()->numerify('############') : null,
            'price' => fake()->randomFloat(2, 1, 500),
            'cost' => fake()->optional(0.7)->randomFloat(2, 0.5, 200),
            'is_active' => true,
            'specification_id' => Specification::factory(),
            'product_tag_id' => fake()->boolean(60) ? ProductTag::factory() : null,
            'paypal_currency' => fake()->randomElement(array_keys(PayPalCurrencyOptions::selectOptions())),
            'brand' => fake()->optional()->company(),
            'product_number' => fake()->optional()->bothify('PN-#####'),
            'media_expansions' => null,
            'product_tones' => null,
            'product_remarks' => fake()->optional()->sentence(),
            'product_details' => null,
            'product_icon' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function requiresAgeVerification(?int $minimumAge = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_age_verification' => true,
            'minimum_age' => $minimumAge,
        ]);
    }
}

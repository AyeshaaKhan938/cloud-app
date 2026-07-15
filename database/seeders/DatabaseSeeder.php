<?php

namespace Database\Seeders;

use App\Enums\SpecificationSellingType;
use App\Enums\UserRegistrationMethod;
use App\Enums\UserRole;
use App\Models\ProductTag;
use App\Models\Specification;
use App\Models\SpecificationType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'account' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'contact_emails' => 'test@example.com',
            'phone' => '+10000000000',
            'timezone' => 'UTC',
            'role' => UserRole::Admin,
            'is_enabled' => true,
            'registration_method' => UserRegistrationMethod::Admin,
            'wallet_balance' => 2.46,
        ]);

        foreach (['Beverages', 'Snacks', 'General'] as $specName) {
            SpecificationType::firstOrCreate(['name' => $specName]);
        }

        foreach (['Featured', 'New', 'On sale'] as $tagName) {
            ProductTag::firstOrCreate(['name' => $tagName]);
        }

        Specification::firstOrCreate(
            ['name' => 'Beverages'],
            [
                'specification_type' => SpecificationSellingType::ByThePiece,
                'value' => null,
                'remarks' => null,
            ]
        );
    }
}

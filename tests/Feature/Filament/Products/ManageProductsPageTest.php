<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageProductsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_products_index(): void
    {
        $this->get(route('filament.admin.resources.products.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_products_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.products.index'))
            ->assertOk();
    }

    public function test_products_list_shows_records(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.products.index'))
            ->assertOk()
            ->assertSee($products->first()->name);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Specifications;

use App\Models\Specification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageSpecificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_specifications_index(): void
    {
        $this->get(route('filament.admin.resources.specifications.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_specifications_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.specifications.index'))
            ->assertOk();
    }

    public function test_specifications_list_shows_records(): void
    {
        $user = User::factory()->create();
        $specifications = Specification::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.specifications.index'))
            ->assertOk()
            ->assertSee($specifications->first()->name);
    }
}

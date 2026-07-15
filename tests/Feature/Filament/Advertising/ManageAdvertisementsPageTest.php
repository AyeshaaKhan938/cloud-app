<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Advertising;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageAdvertisementsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_advertisements_index(): void
    {
        $this->get(route('filament.admin.resources.advertisements.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_advertisements_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisements.index'))
            ->assertOk();
    }

    public function test_advertisements_list_shows_records(): void
    {
        $user = User::factory()->create();
        $advertisements = Advertisement::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisements.index'))
            ->assertOk()
            ->assertSee($advertisements->first()->title);
    }
}

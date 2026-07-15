<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Advertising;

use App\Models\AdvertisementGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageAdvertisementGroupsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_advertisement_groups_index(): void
    {
        $this->get(route('filament.admin.resources.advertisement-groups.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_advertisement_groups_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-groups.index'))
            ->assertOk();
    }

    public function test_advertisement_groups_list_shows_records(): void
    {
        $user = User::factory()->create();
        $groups = AdvertisementGroup::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-groups.index'))
            ->assertOk()
            ->assertSee($groups->first()->name);
    }

    public function test_authenticated_user_can_access_create_advertisement_group_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-groups.create'))
            ->assertOk()
            ->assertSee('Create advertisement group', false);
    }

    public function test_authenticated_user_can_access_edit_advertisement_group_page(): void
    {
        $user = User::factory()->create();
        $group = AdvertisementGroup::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-groups.edit', ['record' => $group]))
            ->assertOk()
            ->assertSee('Edit advertisement group', false);
    }
}

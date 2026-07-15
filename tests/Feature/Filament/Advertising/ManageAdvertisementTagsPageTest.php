<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Advertising;

use App\Models\AdvertisementTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageAdvertisementTagsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_advertisement_tags_index(): void
    {
        $this->get(route('filament.admin.resources.advertisement-tags.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_advertisement_tags_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-tags.index'))
            ->assertOk();
    }

    public function test_advertisement_tags_list_shows_records(): void
    {
        $user = User::factory()->create();
        $tags = AdvertisementTag::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-tags.index'))
            ->assertOk()
            ->assertSee($tags->first()->name);
    }

    public function test_authenticated_user_can_access_create_advertisement_tag_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-tags.create'))
            ->assertOk()
            ->assertSee('Create advertisement tag', false);
    }

    public function test_authenticated_user_can_access_edit_advertisement_tag_page(): void
    {
        $user = User::factory()->create();
        $tag = AdvertisementTag::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.advertisement-tags.edit', ['record' => $tag]))
            ->assertOk()
            ->assertSee('Edit advertisement tag', false);
    }
}

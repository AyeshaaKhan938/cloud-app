<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Brand;

use App\Models\BrandSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_brand_settings_page(): void
    {
        $this->get(route('filament.admin.pages.brand'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_brand_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.brand'))
            ->assertOk();
    }

    public function test_brand_settings_page_shows_default_title_from_database(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.brand'))
            ->assertOk()
            ->assertSee(BrandSetting::current()->default_webpage_title, false);
    }
}

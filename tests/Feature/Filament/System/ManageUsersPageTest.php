<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageUsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_system_users_index(): void
    {
        $this->get(route('filament.admin.resources.system-users.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_system_users_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.system-users.index'))
            ->assertOk();
    }

    public function test_users_table_lists_existing_accounts(): void
    {
        $user = User::factory()->create([
            'account' => 'listme',
            'name' => 'Listed User',
        ]);

        $this->actingAs($user)
            ->get(route('filament.admin.resources.system-users.index'))
            ->assertOk()
            ->assertSee('listme', false)
            ->assertSee('Listed User', false);
    }
}

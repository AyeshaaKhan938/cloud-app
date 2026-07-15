<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\SystemMaintenance;

use App\Models\PushRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManagePushRecordsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_push_records_index(): void
    {
        $this->get(route('filament.admin.resources.push-records.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_push_records_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.push-records.index'))
            ->assertOk();
    }

    public function test_push_records_list_shows_records(): void
    {
        $user = User::factory()->create();
        $records = PushRecord::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.push-records.index'))
            ->assertOk()
            ->assertSee($records->first()->message_title, false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\SystemMaintenance;

use App\Models\InformationStorageRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageInformationStorageRecordsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_information_storage_index(): void
    {
        $this->get(route('filament.admin.resources.information-storage-records.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_information_storage_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.information-storage-records.index'))
            ->assertOk();
    }

    public function test_information_storage_list_shows_records(): void
    {
        $user = User::factory()->create();
        $records = InformationStorageRecord::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.information-storage-records.index'))
            ->assertOk()
            ->assertSee($records->first()->user_name);
    }
}

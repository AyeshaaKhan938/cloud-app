<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Machines;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MachinesModuleRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_machines_module_routes(): void
    {
        foreach (self::machineRouteNames() as $routeName) {
            $this->get(route($routeName))->assertRedirect();
        }
    }

    #[DataProvider('machineRouteNamesProvider')]
    public function test_authenticated_user_can_access_machines_module_routes(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk();
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function machineRouteNamesProvider(): iterable
    {
        foreach (self::machineRouteNames() as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @return list<string>
     */
    private static function machineRouteNames(): array
    {
        return [
            'filament.admin.resources.machines.list.index',
            'filament.admin.resources.machines.groups.index',
            'filament.admin.resources.machines.finance-groups.index',
            'filament.admin.resources.machines.label-groups.index',
            'filament.admin.resources.machines.alarms.index',
            'filament.admin.pages.machines.map',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\Machine;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

final class MachineMap extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Map view';

    protected static string|UnitEnum|null $navigationGroup = 'Machines';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Machine Map';

    protected static ?string $slug = 'machines/map';

    protected string $view = 'filament.admin.pages.machine-map';

    public function getHeading(): string|Htmlable|null
    {
        return 'Machine Map';
    }

    public function getSubheading(): ?string
    {
        return 'Geographic view of machines with coordinates on file.';
    }

    /**
     * @return list<array{id: int, lat: float, lng: float, label: string, number: string}>
     */
    public function getMachinesForMap(): array
    {
        return Machine::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('machine_number')
            ->get()
            ->map(static function (Machine $machine): array {
                return [
                    'id' => (int) $machine->id,
                    'lat' => (float) $machine->latitude,
                    'lng' => (float) $machine->longitude,
                    'number' => (string) $machine->machine_number,
                    'label' => (string) $machine->machine_name,
                ];
            })
            ->values()
            ->all();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}

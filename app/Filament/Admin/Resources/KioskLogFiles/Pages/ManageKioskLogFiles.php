<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KioskLogFiles\Pages;

use App\Filament\Admin\Resources\KioskLogFiles\KioskLogFileResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageKioskLogFiles extends ManageRecords
{
    protected static string $resource = KioskLogFileResource::class;

    /**
     * No "Create" header action — kiosk log files are only ever
     * uploaded by kiosks via POST /api/v1/machines/{machineNo}/logs.
     * Admin browses + deletes them; they don't create them by hand.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}

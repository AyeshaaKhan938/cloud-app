<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Machine;
use App\Services\Kiosk\KioskOperatorAlertService;
use Filament\Widgets\Widget;

final class OperatorAlertsOverview extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.operator-alerts-overview';

    /**
     * @return list<array{machine_number: string, machine_name: string, type: string, severity: string, title: string, message: string}>
     */
    public function getAlerts(): array
    {
        $service = app(KioskOperatorAlertService::class);
        $rows = [];

        foreach (Machine::query()->with('slots')->orderBy('machine_name')->get() as $machine) {
            foreach ($service->alertsFor($machine) as $alert) {
                $rows[] = [
                    'machine_number' => $machine->machine_number,
                    'machine_name' => $machine->machine_name ?? $machine->machine_number,
                    'type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                ];
            }
        }

        return $rows;
    }
}

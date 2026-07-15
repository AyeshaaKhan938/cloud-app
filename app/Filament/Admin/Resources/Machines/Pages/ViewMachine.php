<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Machines\Pages;

use App\Filament\Admin\Resources\Machines\MachineResource;
use App\Models\Machine;
use App\Models\MachineAlarm;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\WorkOrder;
use App\Services\Filament\InterconnectedEntityService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

final class ViewMachine extends Page
{
    use InteractsWithRecord;

    protected static string $resource = MachineResource::class;

    protected static ?string $title = 'Machine';

    protected static ?string $breadcrumb = 'Overview';

    protected string $view = 'filament.admin.resources.machines.pages.view-machine';

    public string $activeTab = 'overview';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(MachineResource::canView($this->getRecord()), 403);

        $tab = request()->query('tab');

        if (is_string($tab) && in_array($tab, ['overview', 'slots', 'related'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Machine $machine */
        $machine = $this->getRecord();

        return $machine->machine_name;
    }

    public function getSubheading(): ?string
    {
        /** @var Machine $machine */
        $machine = $this->getRecord();

        return 'Machine #'.$machine->machine_number.' · '.$this->tabLabel($this->activeTab);
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['overview', 'slots', 'related'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * @return array{
     *     total: int,
     *     stocked: int,
     *     empty: int,
     *     low_stock: int,
     *     fault: int,
     *     unassigned: int
     * }
     */
    public function getSlotSummary(): array
    {
        /** @var Machine $machine */
        $machine = $this->getRecord();

        return app(InterconnectedEntityService::class)->machineSlotSummary($machine);
    }

    /**
     * @return Collection<int, MachineSlot>
     */
    public function getSlotRows()
    {
        /** @var Machine $machine */
        $machine = $this->getRecord();

        return app(InterconnectedEntityService::class)->machineSlots($machine);
    }

    /**
     * @return array{
     *     orders: Collection<int, Order>,
     *     alarms: Collection<int, MachineAlarm>,
     *     tickets: Collection<int, WorkOrder>
     * }
     */
    public function getRelatedRecords(): array
    {
        /** @var Machine $machine */
        $machine = $this->getRecord();

        return app(InterconnectedEntityService::class)->machineRelatedRecords($machine);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageSlots')
                ->label('Manage slots')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->url(fn (): string => MachineResource::getUrl('slots', ['record' => $this->getRecord()]))
                ->color('info'),
            Action::make('back')
                ->label('All machines')
                ->url(MachineResource::getUrl())
                ->color('gray'),
        ];
    }

    private function tabLabel(string $tab): string
    {
        return match ($tab) {
            'slots' => 'Slots & products',
            'related' => 'Related records',
            default => 'Overview',
        };
    }
}

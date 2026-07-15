<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MyWorkOrders\Pages;

use App\Filament\Admin\Resources\MyWorkOrders\MyWorkOrderResource;
use App\Models\WorkOrder;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

final class ViewMyWorkOrder extends Page
{
    use InteractsWithRecord;

    protected static string $resource = MyWorkOrderResource::class;

    protected static ?string $title = 'Support ticket';

    protected static ?string $navigationLabel = 'View ticket';

    protected string $view = 'filament.admin.resources.my-work-orders.pages.view-my-work-order';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()?->can('view', $this->getRecord()) ?? false, 403);
    }

    public function getTitle(): string|Htmlable
    {
        /** @var WorkOrder $ticket */
        $ticket = $this->getRecord();

        return 'Ticket '.$ticket->work_order_number;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

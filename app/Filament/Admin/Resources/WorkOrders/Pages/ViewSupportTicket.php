<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WorkOrders\Pages;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Admin\Resources\WorkOrders\WorkOrderResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Support\WorkOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

final class ViewSupportTicket extends Page
{
    use InteractsWithRecord;

    protected static string $resource = WorkOrderResource::class;

    protected static ?string $title = 'Support ticket';

    protected string $view = 'filament.admin.resources.work-orders.pages.view-support-ticket';

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
        return [
            Action::make('assign')
                ->label('Assign agent')
                ->icon('heroicon-o-user-plus')
                ->visible(fn (): bool => app(WorkOrderService::class)->canManageQueue())
                ->form([
                    Select::make('assigned_to_user_id')
                        ->label('Support agent')
                        ->options(
                            User::query()
                                ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->searchable()
                        ->nullable(),
                ])
                ->fillForm(fn (): array => [
                    'assigned_to_user_id' => $this->getRecord()->assigned_to_user_id,
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    /** @var WorkOrder $ticket */
                    $ticket = $this->getRecord();
                    $assignee = isset($data['assigned_to_user_id'])
                        ? User::query()->find((int) $data['assigned_to_user_id'])
                        : null;

                    app(WorkOrderService::class)->assign($ticket, $assignee, $actor);
                    $this->record = $ticket->fresh();
                }),
            Action::make('markSolved')
                ->label('Mark solved')
                ->color('success')
                ->visible(fn (): bool => app(WorkOrderService::class)->canManageQueue())
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    /** @var WorkOrder $ticket */
                    $ticket = $this->getRecord();

                    app(WorkOrderService::class)->updateStatus($ticket, WorkOrderStatus::Completed, $actor);
                    $this->record = $ticket->fresh();
                }),
        ];
    }
}

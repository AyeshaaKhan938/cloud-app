<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MyWorkOrders\Pages;

use App\Filament\Admin\Resources\MyWorkOrders\MyWorkOrderResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Support\WorkOrderService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageMyWorkOrders extends ManageRecords
{
    protected static string $resource = MyWorkOrderResource::class;

    public function getTitle(): string
    {
        return 'Support tickets';
    }

    public function getSubheading(): ?string
    {
        return 'Report machine issues, track ticket status, and chat with support when an agent is online.';
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1))),
            default => parent::getDefaultActionSchemaResolver($action),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New support ticket')
                ->modalHeading('New support ticket')
                ->modalSubmitActionLabel('Submit ticket')
                ->authorize(fn (): bool => auth()->user()?->can('create', WorkOrder::class) ?? false)
                ->using(function (array $data): WorkOrder {
                    /** @var User $user */
                    $user = auth()->user();

                    return app(WorkOrderService::class)->createTicket($data, $user);
                }),
        ];
    }
}

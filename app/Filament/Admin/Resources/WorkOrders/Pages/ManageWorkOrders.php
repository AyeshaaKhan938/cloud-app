<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WorkOrders\Pages;

use App\Filament\Admin\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Admin\Widgets\SupportQueueOverviewWidget;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageWorkOrders extends ManageRecords
{
    protected static string $resource = WorkOrderResource::class;

    public function getTitle(): string
    {
        return 'Support queue';
    }

    public function getSubheading(): ?string
    {
        return 'Prioritized operator tickets — urgent items first. Assign agents, reply in-thread, and mark solved.';
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SupportQueueOverviewWidget::class,
        ];
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
                ->label('Add ticket')
                ->modalHeading('Add support ticket')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = null;
                    if (blank($data['attachments'] ?? null)) {
                        $data['attachments'] = null;
                    }

                    return $data;
                }),
        ];
    }
}

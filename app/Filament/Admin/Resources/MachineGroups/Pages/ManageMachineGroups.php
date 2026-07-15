<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MachineGroups\Pages;

use App\Filament\Admin\Resources\MachineGroups\MachineGroupResource;
use App\Models\Machine;
use App\Models\MachineGroup;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;

final class ManageMachineGroups extends ManageRecords
{
    protected static string $resource = MachineGroupResource::class;

    public function getTitle(): string
    {
        return 'Machine Group';
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
            Action::make('export')
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->title('Export')
                    ->body('Export will be available when the export pipeline is configured.')
                    ->warning()
                    ->send()),
            CreateAction::make()
                ->label('Add')
                ->modalHeading('Add Machine Group')
                ->modalSubmitActionLabel('Confirm')
                ->modalCancelActionLabel('Cancel')
                ->using(function (array $data, HasActions&HasSchemas $livewire): MachineGroup {
                    $ids = $data['machine_ids'] ?? [];
                    unset($data['machine_ids']);
                    $group = MachineGroup::query()->create(Arr::only($data, ['name', 'operation_and_maintenance_user_id']));
                    if ($ids !== []) {
                        Machine::query()->whereIn('id', $ids)->update(['machine_group_id' => $group->id]);
                    }

                    return $group;
                }),
        ];
    }
}

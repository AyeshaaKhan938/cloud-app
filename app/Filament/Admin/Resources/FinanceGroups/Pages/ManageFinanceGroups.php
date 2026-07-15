<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FinanceGroups\Pages;

use App\Filament\Admin\Resources\FinanceGroups\FinanceGroupResource;
use App\Models\FinanceGroup;
use App\Models\Machine;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

final class ManageFinanceGroups extends ManageRecords
{
    protected static string $resource = FinanceGroupResource::class;

    public function getTitle(): string
    {
        return 'Financial Group';
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
                ->label('Add')
                ->modalHeading('Add Financial Group')
                ->modalSubmitActionLabel('Confirm')
                ->modalCancelActionLabel('Cancel')
                ->using(function (array $data, HasActions&HasSchemas $livewire): FinanceGroup {
                    $ids = $data['machine_ids'] ?? [];
                    unset($data['machine_ids']);
                    $group = FinanceGroup::query()->create(Arr::only($data, ['name', 'finance_user_id', 'remarks']));
                    if ($ids !== []) {
                        Machine::query()->whereIn('id', $ids)->update(['finance_group_id' => $group->id]);
                    }

                    return $group;
                }),
        ];
    }
}

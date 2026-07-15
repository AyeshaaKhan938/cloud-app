<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MachineLabelGroups\Pages;

use App\Filament\Admin\Resources\MachineLabelGroups\MachineLabelGroupResource;
use App\Models\MachineLabelGroup;
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

final class ManageMachineLabelGroups extends ManageRecords
{
    protected static string $resource = MachineLabelGroupResource::class;

    public function getTitle(): string
    {
        return 'Label grouping';
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
                ->modalHeading('Add Label Group')
                ->modalSubmitActionLabel('Confirm')
                ->modalCancelActionLabel('Cancel')
                ->using(function (array $data, HasActions&HasSchemas $livewire): MachineLabelGroup {
                    $ids = $data['machine_ids'] ?? [];
                    unset($data['machine_ids']);
                    $group = MachineLabelGroup::query()->create(Arr::only($data, ['name']));
                    $group->machines()->sync($ids);

                    return $group;
                }),
        ];
    }
}

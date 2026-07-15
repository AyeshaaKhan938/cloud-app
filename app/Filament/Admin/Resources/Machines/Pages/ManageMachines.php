<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Machines\Pages;

use App\Filament\Admin\Resources\Machines\MachineResource;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageMachines extends ManageRecords
{
    protected static string $resource = MachineResource::class;

    public function getTitle(): string
    {
        return 'Machine list';
    }

    public function getSubheading(): ?string
    {
        return 'Status, stock levels, and slot management for every machine in your account.';
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
                ->modalHeading('Add machine')
                ->modalSubmitActionLabel('Confirm')
                ->modalCancelActionLabel('Cancel'),
        ];
    }
}

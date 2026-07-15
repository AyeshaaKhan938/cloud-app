<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PushRecords\Pages;

use App\Filament\Admin\Resources\PushRecords\PushRecordResource;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManagePushRecords extends ManageRecords
{
    protected static string $resource = PushRecordResource::class;

    public function getTitle(): string
    {
        return 'Push record';
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1))),
            default => parent::getDefaultActionSchemaResolver($action),
        };
    }

    /**
     * Log-style screen: no in-panel create action (records come from the push pipeline).
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}

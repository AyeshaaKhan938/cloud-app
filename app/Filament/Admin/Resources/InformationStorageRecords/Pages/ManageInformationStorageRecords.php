<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\InformationStorageRecords\Pages;

use App\Filament\Admin\Resources\InformationStorageRecords\InformationStorageRecordResource;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageInformationStorageRecords extends ManageRecords
{
    protected static string $resource = InformationStorageRecordResource::class;

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
                ->modalHeading('Add information')
                ->modalSubmitActionLabel('Submit')
                ->mutateFormDataUsing(fn (array $data): array => InformationStorageRecordResource::normalizeRuleFields($data)),
            Action::make('export')
                ->label('Export')
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->title('Export')
                    ->body('Export will be available when the export pipeline is configured.')
                    ->warning()
                    ->send()),
            Action::make('memberCardUsageRaw')
                ->label('Member card usage raw')
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->title('Member card usage raw')
                    ->body('This report is not wired yet.')
                    ->warning()
                    ->send()),
            Action::make('push')
                ->label('Push')
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->title('Push')
                    ->body('Push to devices is not configured yet.')
                    ->warning()
                    ->send()),
        ];
    }
}

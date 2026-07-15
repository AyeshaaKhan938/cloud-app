<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RechargeRecords\Pages;

use App\Filament\Admin\Resources\RechargeRecords\RechargeRecordResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

final class ManageRechargeRecords extends ManageRecords
{
    protected static string $resource = RechargeRecordResource::class;

    public function getTitle(): string
    {
        return 'Recharge Record';
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
        ];
    }
}

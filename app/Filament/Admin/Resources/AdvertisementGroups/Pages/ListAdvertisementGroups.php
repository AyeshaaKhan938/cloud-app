<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementGroups\Pages;

use App\Filament\Admin\Resources\AdvertisementGroups\AdvertisementGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAdvertisementGroups extends ListRecords
{
    protected static string $resource = AdvertisementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->url(fn (): string => AdvertisementGroupResource::getUrl('create')),
        ];
    }
}

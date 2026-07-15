<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementTags\Pages;

use App\Filament\Admin\Resources\AdvertisementTags\AdvertisementTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAdvertisementTags extends ListRecords
{
    protected static string $resource = AdvertisementTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add')
                ->url(fn (): string => AdvertisementTagResource::getUrl('create')),
        ];
    }
}

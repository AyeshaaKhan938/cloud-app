<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementTags\Pages;

use App\Filament\Admin\Resources\AdvertisementTags\AdvertisementTagResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class EditAdvertisementTag extends EditRecord
{
    protected static string $resource = AdvertisementTagResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Edit advertisement tag';
    }

    public function defaultForm(Schema $schema): Schema
    {
        $schema = parent::defaultForm($schema);

        return $schema->columns(1);
    }
}

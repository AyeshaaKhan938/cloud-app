<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementTags\Pages;

use App\Filament\Admin\Resources\AdvertisementTags\AdvertisementTagResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class CreateAdvertisementTag extends CreateRecord
{
    protected static string $resource = AdvertisementTagResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Create advertisement tag';
    }

    public function defaultForm(Schema $schema): Schema
    {
        $schema = parent::defaultForm($schema);

        return $schema->columns(1);
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdvertisementType: string implements HasLabel
{
    case Image = 'image';

    case Video = 'video';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Image => 'Image advertisement',
            self::Video => 'Video advertisement',
        };
    }
}

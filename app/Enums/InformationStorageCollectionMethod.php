<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InformationStorageCollectionMethod: string implements HasLabel
{
    case MemberCard = 'member_card';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MemberCard => 'Member card',
        };
    }
}

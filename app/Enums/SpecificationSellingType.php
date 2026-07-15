<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SpecificationSellingType: string implements HasLabel
{
    case ByThePiece = 'by_piece';

    case WeightAmbp500 = 'weight_ambp_500';

    case PerfumeCount = 'perfume_count';

    case OpenDoor = 'open_door';

    case Capacity = 'capacity';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ByThePiece => 'By the piece',
            self::WeightAmbp500 => 'Weight -ambp 500',
            self::PerfumeCount => 'Perfume count',
            self::OpenDoor => 'open_door',
            self::Capacity => 'Capacity',
        };
    }
}

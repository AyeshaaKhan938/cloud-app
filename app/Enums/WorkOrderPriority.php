<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkOrderPriority: string implements HasLabel
{
    case Low = 'low';

    case Normal = 'normal';

    case High = 'high';

    case Urgent = 'urgent';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Normal => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Urgent => 1,
            self::High => 2,
            self::Normal => 3,
            self::Low => 4,
        };
    }
}

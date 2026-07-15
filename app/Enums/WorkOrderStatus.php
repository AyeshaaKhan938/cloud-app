<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkOrderStatus: string implements HasLabel
{
    case Unprocessed = 'unprocessed';

    case Processing = 'processing';

    case Completed = 'completed';

    case Closed = 'closed';

    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Unprocessed => 'Open',
            self::Processing => 'In progress',
            self::Completed => 'Solved',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Unprocessed, self::Processing], true);
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::Completed, self::Closed, self::Cancelled], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Unprocessed => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Closed => 'gray',
            self::Cancelled => 'danger',
        };
    }
}

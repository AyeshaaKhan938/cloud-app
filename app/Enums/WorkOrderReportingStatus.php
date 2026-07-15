<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkOrderReportingStatus: string implements HasLabel
{
    case None = 'none';

    case Pending = 'pending';

    case Submitted = 'submitted';

    case Reviewed = 'reviewed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::None => 'None',
            self::Pending => 'Pending',
            self::Submitted => 'Submitted',
            self::Reviewed => 'Reviewed',
        };
    }
}

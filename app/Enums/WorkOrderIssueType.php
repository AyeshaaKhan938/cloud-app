<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkOrderIssueType: string implements HasLabel
{
    case MachineIssue = 'machine_issue';

    case PricingIssue = 'pricing_issue';

    case OtherIssue = 'other_issue';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MachineIssue => 'Machine issue',
            self::PricingIssue => 'Pricing issue',
            self::OtherIssue => 'Other issue',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum AgeVerificationSessionStatus: string
{
    case Pending = 'pending';

    case Uploaded = 'uploaded';

    case Processing = 'processing';

    case Verified = 'verified';

    case Rejected = 'rejected';

    case Expired = 'expired';
}

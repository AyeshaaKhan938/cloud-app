<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PushMethod: string implements HasLabel
{
    case AppPush = 'app_push';

    case Sms = 'sms';

    case Email = 'email';

    case Wechat = 'wechat';

    case Broadcast = 'broadcast';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AppPush => 'App push',
            self::Sms => 'SMS',
            self::Email => 'Email',
            self::Wechat => 'WeChat',
            self::Broadcast => 'Broadcast',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Concerns;

use App\Models\User;
use App\Services\Users\UserCloudScope;

trait RegistersForPlatformAdmins
{
    public static function shouldRegisterNavigation(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user instanceof User && app(UserCloudScope::class)->hasFullCloudAccess($user);
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }
}

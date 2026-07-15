<?php

declare(strict_types=1);

namespace App\Filament\Admin\Concerns;

use App\Enums\UserFeature;
use App\Services\Users\FeatureAccess;

trait AuthorizesUserFeature
{
    abstract protected static function requiredUserFeature(): UserFeature;

    public static function shouldRegisterNavigation(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return app(FeatureAccess::class)->allowsNavigation(static::requiredUserFeature());
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }
}

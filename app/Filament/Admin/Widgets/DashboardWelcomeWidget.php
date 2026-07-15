<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Services\Users\UserCloudScope;
use Filament\Widgets\Widget;

final class DashboardWelcomeWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -60;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.dashboard-welcome';

    public function getGreeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    public function getUserName(): string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return 'there';
        }

        return $user->name !== '' ? $user->name : $user->account;
    }

    public function getRoleLabel(): string
    {
        $user = auth()->user();

        return $user instanceof User ? (string) $user->role->getLabel() : 'User';
    }

    public function getMessage(): string
    {
        if (app(UserCloudScope::class)->hasFullCloudAccess()) {
            return 'Here is what needs attention across the platform today.';
        }

        return 'Track sales, machines, and support without digging through menus.';
    }
}

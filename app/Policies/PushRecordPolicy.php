<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PushRecord;
use App\Models\User;

final class PushRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PushRecord $pushRecord): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PushRecord $pushRecord): bool
    {
        return true;
    }

    public function delete(User $user, PushRecord $pushRecord): bool
    {
        return true;
    }

    public function restore(User $user, PushRecord $pushRecord): bool
    {
        return true;
    }

    public function forceDelete(User $user, PushRecord $pushRecord): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RechargeRecord;
use App\Models\User;

final class RechargeRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RechargeRecord $rechargeRecord): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RechargeRecord $rechargeRecord): bool
    {
        return false;
    }

    public function delete(User $user, RechargeRecord $rechargeRecord): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, RechargeRecord $rechargeRecord): bool
    {
        return false;
    }

    public function forceDelete(User $user, RechargeRecord $rechargeRecord): bool
    {
        return false;
    }
}

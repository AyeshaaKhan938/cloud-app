<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InformationStorageRecord;
use App\Models\User;

final class InformationStorageRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InformationStorageRecord $informationStorageRecord): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, InformationStorageRecord $informationStorageRecord): bool
    {
        return true;
    }

    public function delete(User $user, InformationStorageRecord $informationStorageRecord): bool
    {
        return true;
    }

    public function restore(User $user, InformationStorageRecord $informationStorageRecord): bool
    {
        return true;
    }

    public function forceDelete(User $user, InformationStorageRecord $informationStorageRecord): bool
    {
        return true;
    }
}

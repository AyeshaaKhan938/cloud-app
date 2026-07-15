<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvertisementGroup;
use App\Models\User;

final class AdvertisementGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AdvertisementGroup $advertisementGroup): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AdvertisementGroup $advertisementGroup): bool
    {
        return true;
    }

    public function delete(User $user, AdvertisementGroup $advertisementGroup): bool
    {
        return true;
    }

    public function restore(User $user, AdvertisementGroup $advertisementGroup): bool
    {
        return true;
    }

    public function forceDelete(User $user, AdvertisementGroup $advertisementGroup): bool
    {
        return true;
    }
}

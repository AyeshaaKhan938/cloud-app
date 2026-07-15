<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvertisementTag;
use App\Models\User;

final class AdvertisementTagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AdvertisementTag $advertisementTag): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AdvertisementTag $advertisementTag): bool
    {
        return true;
    }

    public function delete(User $user, AdvertisementTag $advertisementTag): bool
    {
        return true;
    }

    public function restore(User $user, AdvertisementTag $advertisementTag): bool
    {
        return true;
    }

    public function forceDelete(User $user, AdvertisementTag $advertisementTag): bool
    {
        return true;
    }
}

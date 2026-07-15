<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;

final class AdvertisementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Advertisement $advertisement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Advertisement $advertisement): bool
    {
        return true;
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return true;
    }

    public function restore(User $user, Advertisement $advertisement): bool
    {
        return true;
    }

    public function forceDelete(User $user, Advertisement $advertisement): bool
    {
        return true;
    }
}

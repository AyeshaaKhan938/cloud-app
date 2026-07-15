<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Specification;
use App\Models\User;

final class SpecificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Specification $specification): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Specification $specification): bool
    {
        return true;
    }

    public function delete(User $user, Specification $specification): bool
    {
        return true;
    }

    public function restore(User $user, Specification $specification): bool
    {
        return true;
    }

    public function forceDelete(User $user, Specification $specification): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\Users\UserAccessManager;

final class UserPolicy
{
    public function __construct(
        private readonly UserAccessManager $accessManager,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->accessManager->canManageUsers($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->accessManager->canManageTarget($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->accessManager->canManageUsers($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->accessManager->canManageTarget($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $this->accessManager->canManageTarget($user, $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }
}

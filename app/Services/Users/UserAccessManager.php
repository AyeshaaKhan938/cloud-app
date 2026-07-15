<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UserAccessManager
{
    public function canManageUsers(?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return $actor instanceof User && $actor->role->canManageUsers();
    }

    public function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return $actor->role === UserRole::SuperAdmin;
        }

        if (! $this->canManageUsers($actor)) {
            return false;
        }

        if ($actor->role === UserRole::SuperAdmin) {
            return true;
        }

        return ! $target->role->hasFullCloudAccess();
    }

    public function assertCanAssignRole(User $actor, UserRole $role): void
    {
        if (! $this->canManageUsers($actor)) {
            throw new RuntimeException('You are not allowed to manage users.');
        }

        if (! $actor->role->canAssignRole($role)) {
            throw new RuntimeException('You are not allowed to assign the '.$role->getLabel().' role.');
        }
    }

    /**
     * @return list<int>
     */
    public function machineIdsForUser(User $user): array
    {
        return $user->machines()->pluck('id')->all();
    }

    /**
     * @param  list<int|string>  $machineIds
     */
    public function syncMachineAccess(User $user, array $machineIds): void
    {
        if ($user->role->hasFullCloudAccess()) {
            return;
        }

        $machineIds = array_values(array_unique(array_map('intval', $machineIds)));

        DB::transaction(function () use ($user, $machineIds): void {
            Machine::query()
                ->where('user_id', $user->id)
                ->when(
                    $machineIds !== [],
                    fn ($query) => $query->whereNotIn('id', $machineIds),
                )
                ->update(['user_id' => null]);

            if ($machineIds === []) {
                return;
            }

            Machine::query()
                ->whereIn('id', $machineIds)
                ->update(['user_id' => $user->id]);
        });
    }

    public function cloudAccessSummary(User $user): string
    {
        if ($user->role->hasFullCloudAccess()) {
            return 'Full cloud access (all machines and data)';
        }

        $count = $user->machines()->count();

        return $count === 1
            ? 'Scoped access — 1 bound machine'
            : 'Scoped access — '.$count.' bound machines';
    }
}

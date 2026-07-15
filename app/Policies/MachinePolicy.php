<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserFeature;
use App\Models\Machine;
use App\Models\User;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;

final class MachinePolicy
{
    public function __construct(
        private readonly UserCloudScope $cloudScope,
        private readonly FeatureAccess $featureAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->featureAccess->allowsAny(
            $user,
            UserFeature::MachinesView,
            UserFeature::MachinesCreate,
            UserFeature::MachineSlots,
        );
    }

    public function view(User $user, Machine $machine): bool
    {
        return $this->cloudScope->ownsMachine($machine, $user)
            && $this->featureAccess->allowsAny(
                $user,
                UserFeature::MachinesView,
                UserFeature::MachinesCreate,
                UserFeature::MachineSlots,
            );
    }

    public function create(User $user): bool
    {
        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        return $this->featureAccess->can($user, UserFeature::MachinesCreate);
    }

    public function update(User $user, Machine $machine): bool
    {
        if (! $this->cloudScope->ownsMachine($machine, $user)) {
            return false;
        }

        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        return $this->featureAccess->allowsAny(
            $user,
            UserFeature::MachinesCreate,
            UserFeature::MachineSlots,
        );
    }

    public function delete(User $user, Machine $machine): bool
    {
        return $this->cloudScope->hasFullCloudAccess($user);
    }

    public function restore(User $user, Machine $machine): bool
    {
        return $this->cloudScope->hasFullCloudAccess($user);
    }

    public function forceDelete(User $user, Machine $machine): bool
    {
        return $this->cloudScope->hasFullCloudAccess($user);
    }
}

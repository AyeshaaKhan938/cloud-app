<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserFeature;
use App\Models\Product;
use App\Models\User;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;

final class ProductPolicy
{
    public function __construct(
        private readonly UserCloudScope $cloudScope,
        private readonly FeatureAccess $featureAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        return $this->featureAccess->allowsAny(
            $user,
            UserFeature::Products,
            UserFeature::MachineSlots,
        );
    }

    public function view(User $user, Product $product): bool
    {
        return $this->cloudScope->canAccessProduct($product, $user);
    }

    public function create(User $user): bool
    {
        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        if (! $user->role->canManageOwnCatalog()) {
            return false;
        }

        return $this->featureAccess->can($user, UserFeature::Products);
    }

    public function update(User $user, Product $product): bool
    {
        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        if (! $this->featureAccess->can($user, UserFeature::Products)) {
            return false;
        }

        return $this->cloudScope->canAccessProduct($product, $user);
    }

    public function delete(User $user, Product $product): bool
    {
        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        if (! $this->featureAccess->can($user, UserFeature::Products)) {
            return false;
        }

        $owner = $this->cloudScope->accountOwner($user);

        return $product->user_id === $owner->id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->cloudScope->hasFullCloudAccess($user);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $this->cloudScope->hasFullCloudAccess($user);
    }
}

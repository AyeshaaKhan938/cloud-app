<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductLottery;
use App\Models\User;
use App\Services\Users\UserCloudScope;

final class ProductLotteryPolicy
{
    public function __construct(
        private readonly UserCloudScope $cloudScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductLottery $productLottery): bool
    {
        return $this->cloudScope->canAccessLottery($productLottery, $user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProductLottery $productLottery): bool
    {
        return $this->cloudScope->canAccessLottery($productLottery, $user);
    }

    public function delete(User $user, ProductLottery $productLottery): bool
    {
        return $this->cloudScope->canAccessLottery($productLottery, $user);
    }

    public function restore(User $user, ProductLottery $productLottery): bool
    {
        return $this->cloudScope->canAccessLottery($productLottery, $user);
    }

    public function forceDelete(User $user, ProductLottery $productLottery): bool
    {
        return $this->cloudScope->canAccessLottery($productLottery, $user);
    }
}

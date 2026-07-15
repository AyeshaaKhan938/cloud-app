<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

final class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return true;
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return true;
    }

    public function restore(User $user, Coupon $coupon): bool
    {
        return true;
    }

    public function forceDelete(User $user, Coupon $coupon): bool
    {
        return true;
    }
}

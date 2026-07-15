<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CouponCode;
use App\Models\User;

final class CouponCodePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CouponCode $couponCode): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CouponCode $couponCode): bool
    {
        return true;
    }

    public function delete(User $user, CouponCode $couponCode): bool
    {
        return true;
    }

    public function restore(User $user, CouponCode $couponCode): bool
    {
        return true;
    }

    public function forceDelete(User $user, CouponCode $couponCode): bool
    {
        return true;
    }
}

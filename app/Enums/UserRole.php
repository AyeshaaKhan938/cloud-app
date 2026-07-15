<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case SuperAdmin = 'super_admin';

    case Admin = 'admin';

    case Agency = 'agency';

    case Operator = 'operator';

    case Customer = 'customer';

    case SubAccount = 'sub_account';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Admin => 'Admin',
            self::Agency => 'Agency',
            self::Operator => 'Operator',
            self::Customer => 'Customer',
            self::SubAccount => 'Sub-account',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full platform access. Can manage all users, roles, and cloud data.',
            self::Admin => 'Platform administrator. Can manage client users and all cloud data.',
            self::Agency => 'Partner account. Sees only machines and sales bound to this account.',
            self::Operator => 'Field operator. Sees only machines and sales bound to this account.',
            self::Customer => 'Machine owner / client. Sees only their own machines and related data.',
            self::SubAccount => 'Team member under a client account. Access is limited to assigned features.',
        };
    }

    public function hasFullCloudAccess(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function canManageUsers(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function requiresMachineScoping(): bool
    {
        return ! $this->hasFullCloudAccess();
    }

    public function usesFeaturePermissions(): bool
    {
        return $this === self::SubAccount;
    }

    public function canManageOwnCatalog(): bool
    {
        return in_array($this, [self::Agency, self::Operator, self::Customer, self::SubAccount], true);
    }

    /**
     * @return list<self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::SuperAdmin => self::cases(),
            default => [],
        };
    }

    public function canAssignRole(self $targetRole): bool
    {
        return in_array($targetRole, $this->assignableRoles(), true);
    }
}

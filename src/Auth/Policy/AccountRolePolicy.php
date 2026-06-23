<?php

declare(strict_types=1);

namespace Core\Auth\Policy;

final class AccountRolePolicy
{
    /** @param list<string> $allowedRoles */
    public function __construct(
        private readonly array $allowedRoles = ['admin', 'manager', 'user'],
    ) {
    }

    public function normalize(string $role): string
    {
        return strtolower(trim($role));
    }

    public function isAllowed(string $role): bool
    {
        return in_array($this->normalize($role), $this->allowedRoles, true);
    }

    /**
     * @return list<string>
     */
    public function allowedRoles(): array
    {
        return $this->allowedRoles;
    }
}

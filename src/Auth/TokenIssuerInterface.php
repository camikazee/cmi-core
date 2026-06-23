<?php

declare(strict_types=1);

namespace Core\Auth;

use Symfony\Component\Security\Core\User\UserInterface;

interface TokenIssuerInterface
{
    public function issue(UserInterface $user): string;
}

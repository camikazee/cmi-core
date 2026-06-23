<?php

declare(strict_types=1);

namespace Core\Auth\Service;

use Core\Auth\TokenIssuerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthTokenService
{
    public function __construct(
        private readonly TokenIssuerInterface $tokenIssuer,
    ) {
    }

    /**
     * @return array{token:string}
     */
    public function payloadFor(UserInterface $user): array
    {
        return [
            'token' => $this->tokenIssuer->issue($user),
        ];
    }
}

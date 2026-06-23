<?php

declare(strict_types=1);

namespace Core\Auth\Security;

final class VerificationCodeGenerator
{
    public function generate6Digit(): string
    {
        return (string) str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}


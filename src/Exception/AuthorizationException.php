<?php

declare(strict_types=1);

namespace Core\Exception;

use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * Class AuthorizationException.
 */
class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous);
    }
}

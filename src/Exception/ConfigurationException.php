<?php

declare(strict_types=1);

namespace Core\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * Class ConfigurationException.
 */
class ConfigurationException extends RuntimeException
{
    public function __construct(string $message = '', int $code = Response::HTTP_BAD_REQUEST, ?\Exception $previous = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous);
    }
}

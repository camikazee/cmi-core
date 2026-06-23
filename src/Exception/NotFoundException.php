<?php

declare(strict_types=1);

namespace Core\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * Class NotFoundException.
 */
class NotFoundException extends RuntimeException
{
    public function __construct(string $message = '', int $code = Response::HTTP_NOT_FOUND, ?\Exception $previous = null)
    {
        parent::__construct($message ?: $this->__toString(), $code, $previous);
    }
}

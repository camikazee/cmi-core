<?php

declare(strict_types=1);

namespace Core\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * Class InvalidDataException.
 */
class InvalidDataException extends RuntimeException
{
    private string $field;

    public function __construct(string $field, string $message = '', int $code = Response::HTTP_UNPROCESSABLE_ENTITY, ?\Exception $previous = null)
    {
        $this->field = $field;

        parent::__construct($message ?: $this->__toString(), $code, $previous);
    }

    public function getField(): string
    {
        return $this->field;
    }
}

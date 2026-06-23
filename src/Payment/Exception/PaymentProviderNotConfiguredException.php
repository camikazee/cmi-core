<?php

declare(strict_types=1);

namespace Core\Payment\Exception;

final class PaymentProviderNotConfiguredException extends \RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self(sprintf('Operator płatności "%s" nie jest skonfigurowany.', $code));
    }
}

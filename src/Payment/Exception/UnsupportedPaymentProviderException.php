<?php

declare(strict_types=1);

namespace Core\Payment\Exception;

final class UnsupportedPaymentProviderException extends \RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self(sprintf('Nieobsługiwany operator płatności: "%s".', $code));
    }
}

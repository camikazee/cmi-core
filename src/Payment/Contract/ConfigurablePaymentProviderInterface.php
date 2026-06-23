<?php

declare(strict_types=1);

namespace Core\Payment\Contract;

/**
 * Adapter wymagający konfiguracji (sekrety/klucze). Pozwala registry sprawdzić dostępność.
 */
interface ConfigurablePaymentProviderInterface
{
    public function isConfigured(): bool;
}

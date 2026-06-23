<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

use Core\Payment\Contract\ConfigurablePaymentProviderInterface;
use Core\Payment\Contract\PaymentProviderInterface;
use Core\Payment\Exception\UnsupportedPaymentProviderException;

final class PaymentProviderRegistry
{
    /** @var array<string, PaymentProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<PaymentProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->code()] = $provider;
        }
    }

    public function get(string $providerCode): PaymentProviderInterface
    {
        return $this->providers[$providerCode]
            ?? throw UnsupportedPaymentProviderException::forCode($providerCode);
    }

    public function has(string $providerCode): bool
    {
        return isset($this->providers[$providerCode]);
    }

    /** Operator istnieje i (jeśli konfigurowalny) jest skonfigurowany. */
    public function isAvailable(string $providerCode): bool
    {
        $provider = $this->providers[$providerCode] ?? null;
        if ($provider === null) {
            return false;
        }

        return !$provider instanceof ConfigurablePaymentProviderInterface || $provider->isConfigured();
    }

    /** @return list<string> */
    public function codes(): array
    {
        return array_values(array_keys($this->providers));
    }

    /** @return list<string> tylko dostępne (skonfigurowane) operatory */
    public function availableCodes(): array
    {
        return array_values(array_filter($this->codes(), fn (string $code): bool => $this->isAvailable($code)));
    }
}

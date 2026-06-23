<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

final readonly class PayUConfig
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $merchantPosId,
        public string $secondKey,
        public string $baseUrl = 'https://secure.snd.payu.com',
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->clientId) !== ''
            && trim($this->clientSecret) !== ''
            && trim($this->merchantPosId) !== ''
            && trim($this->secondKey) !== ''
            && filter_var($this->baseUrl, FILTER_VALIDATE_URL) !== false;
    }

    public function normalizedBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }
}

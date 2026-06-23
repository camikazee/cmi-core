<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

final readonly class PayPalConfig
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $webhookId,
        public string $baseUrl = 'https://api-m.sandbox.paypal.com',
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->clientId) !== ''
            && trim($this->clientSecret) !== ''
            && trim($this->webhookId) !== ''
            && filter_var($this->baseUrl, FILTER_VALIDATE_URL) !== false;
    }

    public function normalizedBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }
}

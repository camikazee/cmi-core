<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

final readonly class Przelewy24Config
{
    public function __construct(
        public int $merchantId,
        public int $posId,
        public string $apiKey,
        public string $crc,
        public string $baseUrl = 'https://sandbox.przelewy24.pl',
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->merchantId > 0
            && $this->posId > 0
            && trim($this->apiKey) !== ''
            && trim($this->crc) !== ''
            && filter_var($this->baseUrl, FILTER_VALIDATE_URL) !== false;
    }

    public function normalizedBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }
}

<?php

declare(strict_types=1);

namespace Core\Payment\Dto;

final readonly class PaymentWebhookRequest
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>  $payload
     */
    public function __construct(
        public string $providerCode,
        public array $headers,
        public array $payload,
        public string $rawBody,
    ) {
    }
}

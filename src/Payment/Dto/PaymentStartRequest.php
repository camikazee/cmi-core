<?php

declare(strict_types=1);

namespace Core\Payment\Dto;

final readonly class PaymentStartRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $reference,
        public PaymentMoney $money,
        public string $description,
        public string $customerEmail,
        public string $returnUrl,
        public string $webhookUrl,
        public array $metadata = [],
    ) {
    }
}

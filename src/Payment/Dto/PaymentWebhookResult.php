<?php

declare(strict_types=1);

namespace Core\Payment\Dto;

use Core\Payment\Enum\PaymentProviderStatus;

final readonly class PaymentWebhookResult
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public string $providerCode,
        public string $providerTransactionId,
        public PaymentProviderStatus $status,
        public ?PaymentMoney $paidMoney = null,
        public ?\DateTimeImmutable $occurredAt = null,
        public array $rawPayload = [],
    ) {
    }
}

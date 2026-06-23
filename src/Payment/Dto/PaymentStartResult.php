<?php

declare(strict_types=1);

namespace Core\Payment\Dto;

use Core\Payment\Enum\PaymentProviderStatus;

final readonly class PaymentStartResult
{
    /**
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public string $providerCode,
        public string $providerTransactionId,
        public PaymentProviderStatus $status,
        public ?string $redirectUrl = null,
        public array $rawResponse = [],
    ) {
    }
}

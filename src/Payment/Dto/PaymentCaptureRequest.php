<?php

declare(strict_types=1);

namespace Core\Payment\Dto;

final readonly class PaymentCaptureRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $providerTransactionId,
        public array $metadata = [],
    ) {
    }
}

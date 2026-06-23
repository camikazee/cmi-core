<?php

declare(strict_types=1);

namespace Core\Payment\Contract;

use Core\Payment\Dto\PaymentCaptureRequest;
use Core\Payment\Dto\PaymentWebhookResult;

/**
 * Adapter wspierający ręczny capture (np. PayU/PayPal po autoryzacji).
 */
interface CapturablePaymentProviderInterface extends PaymentProviderInterface
{
    public function capture(PaymentCaptureRequest $request): PaymentWebhookResult;
}

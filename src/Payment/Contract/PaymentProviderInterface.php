<?php

declare(strict_types=1);

namespace Core\Payment\Contract;

use Core\Payment\Dto\PaymentStartRequest;
use Core\Payment\Dto\PaymentStartResult;
use Core\Payment\Dto\PaymentWebhookRequest;
use Core\Payment\Dto\PaymentWebhookResult;

/**
 * Port bramki płatniczej (ports-and-adapters). Każdy operator = osobny adapter.
 */
interface PaymentProviderInterface
{
    /** Stabilny kod operatora, np. 'manual', 'przelewy24', 'payu', 'paypal'. */
    public function code(): string;

    /** Inicjuje płatność i zwraca m.in. URL do przekierowania (jeśli online). */
    public function startPayment(PaymentStartRequest $request): PaymentStartResult;

    /** Przetwarza powiadomienie od operatora (z weryfikacją podpisu). */
    public function handleWebhook(PaymentWebhookRequest $request): PaymentWebhookResult;
}

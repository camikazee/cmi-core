<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

use Core\Exception\InvalidDataException;
use Core\Payment\Contract\PaymentProviderInterface;
use Core\Payment\Dto\PaymentMoney;
use Core\Payment\Dto\PaymentStartRequest;
use Core\Payment\Dto\PaymentStartResult;
use Core\Payment\Dto\PaymentWebhookRequest;
use Core\Payment\Dto\PaymentWebhookResult;
use Core\Payment\Enum\PaymentProviderStatus;

/**
 * Operator manualny — bez realnej bramki. Domyślny w dev/test; potwierdzenie przez webhook admina.
 */
final class ManualPaymentProvider implements PaymentProviderInterface
{
    public const CODE = 'manual';

    public function code(): string
    {
        return self::CODE;
    }

    public function startPayment(PaymentStartRequest $request): PaymentStartResult
    {
        return new PaymentStartResult(
            providerCode: self::CODE,
            providerTransactionId: $this->transactionId($request->reference),
            status: PaymentProviderStatus::Pending,
            redirectUrl: null,
            rawResponse: [
                'reference' => $request->reference,
                'amount' => $request->money->amount,
                'currency' => $request->money->currency,
            ],
        );
    }

    public function handleWebhook(PaymentWebhookRequest $request): PaymentWebhookResult
    {
        $transactionId = $request->payload['transactionId'] ?? null;
        if (!is_string($transactionId) || trim($transactionId) === '') {
            throw new InvalidDataException('Brak pola "transactionId" w payloadzie manual webhooka.');
        }

        $statusValue = $request->payload['status'] ?? null;
        $status = is_string($statusValue) ? PaymentProviderStatus::tryFrom($statusValue) : null;
        if (!$status instanceof PaymentProviderStatus) {
            throw new InvalidDataException('Nieobsługiwany status płatności manual.');
        }

        $amount = $request->payload['amount'] ?? null;
        $currency = $request->payload['currency'] ?? 'PLN';

        return new PaymentWebhookResult(
            providerCode: self::CODE,
            providerTransactionId: $transactionId,
            status: $status,
            paidMoney: is_string($amount) ? new PaymentMoney($amount, is_string($currency) ? $currency : 'PLN') : null,
            occurredAt: new \DateTimeImmutable(),
            rawPayload: $request->payload,
        );
    }

    private function transactionId(string $reference): string
    {
        return sprintf('manual_%s', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reference) ?? $reference);
    }
}

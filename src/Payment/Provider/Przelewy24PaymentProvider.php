<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

use Core\Exception\InvalidDataException;
use Core\Payment\Contract\ConfigurablePaymentProviderInterface;
use Core\Payment\Contract\PaymentProviderInterface;
use Core\Payment\Dto\PaymentMoney;
use Core\Payment\Dto\PaymentStartRequest;
use Core\Payment\Dto\PaymentStartResult;
use Core\Payment\Dto\PaymentWebhookRequest;
use Core\Payment\Dto\PaymentWebhookResult;
use Core\Payment\Enum\PaymentProviderStatus;
use Core\Payment\Exception\PaymentProviderNotConfiguredException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adapter Przelewy24 (REST API v1): register + verify, podpis SHA384(JSON + CRC).
 */
final readonly class Przelewy24PaymentProvider implements PaymentProviderInterface, ConfigurablePaymentProviderInterface
{
    public const CODE = 'przelewy24';

    public function __construct(
        private Przelewy24Config $config,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function code(): string
    {
        return self::CODE;
    }

    public function isConfigured(): bool
    {
        return $this->config->isConfigured();
    }

    public function startPayment(PaymentStartRequest $request): PaymentStartResult
    {
        $this->denyUnlessConfigured();

        $amount = $request->money->minorUnits();
        $sessionId = $this->sessionId($request->reference);

        $payload = [
            'merchantId' => $this->config->merchantId,
            'posId' => $this->config->posId,
            'sessionId' => $sessionId,
            'amount' => $amount,
            'currency' => $request->money->currency,
            'description' => $request->description,
            'email' => $request->customerEmail,
            'country' => 'PL',
            'language' => 'pl',
            'urlReturn' => $request->returnUrl,
            'urlStatus' => $request->webhookUrl,
            'sign' => $this->sign([
                'sessionId' => $sessionId,
                'merchantId' => $this->config->merchantId,
                'amount' => $amount,
                'currency' => $request->money->currency,
                'crc' => $this->config->crc,
            ]),
        ];

        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/api/v1/transaction/register', [
            'auth_basic' => [(string) $this->config->posId, $this->config->apiKey],
            'json' => $payload,
        ]);

        $data = $response->toArray(false);
        $token = $data['data']['token'] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new InvalidDataException('Odpowiedź Przelewy24 (register) nie zawiera tokenu transakcji.');
        }

        return new PaymentStartResult(
            providerCode: self::CODE,
            providerTransactionId: $sessionId,
            status: PaymentProviderStatus::Pending,
            redirectUrl: $this->config->normalizedBaseUrl() . '/trnRequest/' . $token,
            rawResponse: ['request' => $payload, 'response' => $data],
        );
    }

    public function handleWebhook(PaymentWebhookRequest $request): PaymentWebhookResult
    {
        $this->denyUnlessConfigured();

        $sessionId = $this->payloadString($request->payload, 'sessionId', 'p24_session_id');
        $amount = $this->payloadString($request->payload, 'amount', 'p24_amount');
        $currency = $this->payloadStringOrNull($request->payload, 'currency', 'p24_currency') ?? 'PLN';
        $orderId = $this->payloadString($request->payload, 'orderId', 'p24_order_id');
        $receivedSign = $this->payloadString($request->payload, 'sign', 'p24_sign');

        $expectedSign = $this->sign([
            'sessionId' => $sessionId,
            'orderId' => (int) $orderId,
            'amount' => (int) $amount,
            'currency' => $currency,
            'crc' => $this->config->crc,
        ]);
        if (!hash_equals($expectedSign, $receivedSign)) {
            throw new InvalidDataException('Niepoprawny podpis webhooka Przelewy24.');
        }

        $verifyPayload = [
            'merchantId' => $this->config->merchantId,
            'posId' => $this->config->posId,
            'sessionId' => $sessionId,
            'amount' => (int) $amount,
            'currency' => $currency,
            'orderId' => (int) $orderId,
            'sign' => $expectedSign,
        ];
        $verify = $this->httpClient->request('PUT', $this->config->normalizedBaseUrl() . '/api/v1/transaction/verify', [
            'auth_basic' => [(string) $this->config->posId, $this->config->apiKey],
            'json' => $verifyPayload,
        ]);
        $verifyData = $verify->toArray(false);
        $this->assertVerifyAccepted($verify->getStatusCode(), $verifyData);

        return new PaymentWebhookResult(
            providerCode: self::CODE,
            providerTransactionId: $sessionId,
            status: PaymentProviderStatus::Paid,
            paidMoney: PaymentMoney::fromMinorUnits((int) $amount, $currency),
            occurredAt: new \DateTimeImmutable(),
            rawPayload: ['webhook' => $request->payload, 'verify' => $verifyData],
        );
    }

    private function denyUnlessConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw PaymentProviderNotConfiguredException::forCode(self::CODE);
        }
    }

    private function sessionId(string $reference): string
    {
        return substr(preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reference) ?? $reference, 0, 100);
    }

    /** @param array<string, mixed> $payload */
    private function sign(array $payload): string
    {
        return hash('sha384', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadString(array $payload, string ...$keys): string
    {
        $value = $this->payloadStringOrNull($payload, ...$keys);
        if ($value === null) {
            throw new InvalidDataException(sprintf('Brak pola "%s" w payloadzie Przelewy24.', implode('|', $keys)));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadStringOrNull(array $payload, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && (is_string($payload[$key]) || is_int($payload[$key]))) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $verifyData */
    private function assertVerifyAccepted(int $statusCode, array $verifyData): void
    {
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new InvalidDataException('Weryfikacja transakcji Przelewy24 nie powiodła się (HTTP).');
        }
        $error = $verifyData['error'] ?? null;
        if ($error === true || is_array($error) || (is_string($error) && trim($error) !== '')) {
            throw new InvalidDataException('Weryfikacja transakcji Przelewy24 nie powiodła się (error).');
        }
        if (array_key_exists('data', $verifyData) && $verifyData['data'] === false) {
            throw new InvalidDataException('Weryfikacja transakcji Przelewy24 nie powiodła się (data=false).');
        }
    }
}

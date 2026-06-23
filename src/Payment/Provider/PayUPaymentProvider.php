<?php

declare(strict_types=1);

namespace Core\Payment\Provider;

use Core\Exception\InvalidDataException;
use Core\Payment\Contract\CapturablePaymentProviderInterface;
use Core\Payment\Contract\ConfigurablePaymentProviderInterface;
use Core\Payment\Dto\PaymentCaptureRequest;
use Core\Payment\Dto\PaymentStartRequest;
use Core\Payment\Dto\PaymentStartResult;
use Core\Payment\Dto\PaymentWebhookRequest;
use Core\Payment\Dto\PaymentWebhookResult;
use Core\Payment\Enum\PaymentProviderStatus;
use Core\Payment\Exception\PaymentProviderNotConfiguredException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adapter PayU (REST v2.1): OAuth2 client_credentials → orders; webhook MD5(rawBody + secondKey).
 */
final class PayUPaymentProvider implements CapturablePaymentProviderInterface, ConfigurablePaymentProviderInterface
{
    public const CODE = 'payu';

    public function __construct(
        private readonly PayUConfig $config,
        private readonly HttpClientInterface $httpClient,
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
        $token = $this->accessToken();
        $amount = (string) $request->money->minorUnits();
        $extOrderId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '_', $request->reference) ?? $request->reference, 0, 100);
        $customerIp = is_string($request->metadata['customerIp'] ?? null) ? $request->metadata['customerIp'] : '127.0.0.1';

        $payload = [
            'notifyUrl' => $request->webhookUrl,
            'continueUrl' => $request->returnUrl,
            'customerIp' => $customerIp,
            'merchantPosId' => $this->config->merchantPosId,
            'description' => $request->description,
            'currencyCode' => $request->money->currency,
            'totalAmount' => $amount,
            'extOrderId' => $extOrderId,
            'buyer' => ['email' => $request->customerEmail, 'language' => 'pl'],
            'products' => [['name' => $request->description, 'unitPrice' => $amount, 'quantity' => '1']],
        ];

        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/api/v2_1/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json' => $payload,
            'max_redirects' => 0,
        ]);
        $data = $response->toArray(false);

        $orderId = $data['orderId'] ?? null;
        if (!is_string($orderId) || trim($orderId) === '') {
            throw new InvalidDataException('Odpowiedź PayU (orders) nie zawiera orderId.');
        }
        $redirectUrl = $data['redirectUri'] ?? null;

        return new PaymentStartResult(
            providerCode: self::CODE,
            providerTransactionId: $orderId,
            status: PaymentProviderStatus::Pending,
            redirectUrl: is_string($redirectUrl) ? $redirectUrl : null,
            rawResponse: ['request' => $payload, 'response' => $data],
        );
    }

    public function handleWebhook(PaymentWebhookRequest $request): PaymentWebhookResult
    {
        $this->denyUnlessConfigured();
        $this->verifySignature($request);

        $order = $request->payload['order'] ?? null;
        if (!is_array($order)) {
            throw new InvalidDataException('Brak obiektu "order" w webhooku PayU.');
        }
        $orderId = $order['orderId'] ?? null;
        if (!is_string($orderId) || trim($orderId) === '') {
            throw new InvalidDataException('Brak "order.orderId" w webhooku PayU.');
        }
        $status = $this->mapStatus(is_string($order['status'] ?? null) ? $order['status'] : '');

        return new PaymentWebhookResult(
            providerCode: self::CODE,
            providerTransactionId: $orderId,
            status: $status,
            paidMoney: null,
            occurredAt: new \DateTimeImmutable(),
            rawPayload: $request->payload,
        );
    }

    public function capture(PaymentCaptureRequest $request): PaymentWebhookResult
    {
        $this->denyUnlessConfigured();
        $token = $this->accessToken();

        $this->httpClient->request('PUT', $this->config->normalizedBaseUrl() . '/api/v2_1/orders/' . $request->providerTransactionId . '/status', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json' => ['orderId' => $request->providerTransactionId, 'orderStatus' => 'COMPLETED'],
        ])->getStatusCode();

        return new PaymentWebhookResult(
            providerCode: self::CODE,
            providerTransactionId: $request->providerTransactionId,
            status: PaymentProviderStatus::Paid,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    private function accessToken(): string
    {
        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/pl/standard/user/oauth/authorize', [
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config->clientId,
                'client_secret' => $this->config->clientSecret,
            ],
        ]);
        $token = $response->toArray(false)['access_token'] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new InvalidDataException('Nie udało się uzyskać tokenu OAuth2 PayU.');
        }

        return $token;
    }

    private function verifySignature(PaymentWebhookRequest $request): void
    {
        $header = $request->headers['openpayu-signature'] ?? $request->headers['x-openpayu-signature'] ?? '';
        $params = [];
        foreach (explode(';', $header) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $params[trim($kv[0])] = trim($kv[1]);
            }
        }
        $received = $params['signature'] ?? '';
        $expected = md5($request->rawBody . $this->config->secondKey);
        if ($received === '' || !hash_equals($expected, $received)) {
            throw new InvalidDataException('Niepoprawny podpis webhooka PayU.');
        }
    }

    private function mapStatus(string $payuStatus): PaymentProviderStatus
    {
        return match (strtoupper($payuStatus)) {
            'COMPLETED' => PaymentProviderStatus::Paid,
            'CANCELED', 'CANCELLED' => PaymentProviderStatus::Cancelled,
            'WAITING_FOR_CONFIRMATION' => PaymentProviderStatus::AwaitingConfirmation,
            'REJECTED' => PaymentProviderStatus::Failed,
            default => PaymentProviderStatus::Pending,
        };
    }

    private function denyUnlessConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw PaymentProviderNotConfiguredException::forCode(self::CODE);
        }
    }
}

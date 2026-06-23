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
 * Adapter PayPal (Orders v2, intent CAPTURE): OAuth2 → orders; webhook verify via API.
 */
final class PayPalPaymentProvider implements CapturablePaymentProviderInterface, ConfigurablePaymentProviderInterface
{
    public const CODE = 'paypal';

    public function __construct(
        private readonly PayPalConfig $config,
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

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'custom_id' => $request->reference,
                'description' => $request->description,
                'amount' => ['currency_code' => $request->money->currency, 'value' => $request->money->amount],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $request->returnUrl,
                'cancel_url' => $request->returnUrl,
            ]]],
        ];

        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/v2/checkout/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json' => $payload,
        ]);
        $data = $response->toArray(false);

        $orderId = $data['id'] ?? null;
        if (!is_string($orderId) || trim($orderId) === '') {
            throw new InvalidDataException('Odpowiedź PayPal (orders) nie zawiera id.');
        }

        return new PaymentStartResult(
            providerCode: self::CODE,
            providerTransactionId: $orderId,
            status: PaymentProviderStatus::Pending,
            redirectUrl: $this->approveUrl($data),
            rawResponse: ['request' => $payload, 'response' => $data],
        );
    }

    public function handleWebhook(PaymentWebhookRequest $request): PaymentWebhookResult
    {
        $this->denyUnlessConfigured();
        $this->verifySignature($request);

        $resource = $request->payload['resource'] ?? null;
        if (!is_array($resource)) {
            throw new InvalidDataException('Brak "resource" w webhooku PayPal.');
        }
        $orderId = $resource['id'] ?? null;
        if (!is_string($orderId) || trim($orderId) === '') {
            throw new InvalidDataException('Brak "resource.id" w webhooku PayPal.');
        }
        $eventType = is_string($request->payload['event_type'] ?? null) ? $request->payload['event_type'] : '';

        return new PaymentWebhookResult(
            providerCode: self::CODE,
            providerTransactionId: $orderId,
            status: $this->mapStatus($eventType),
            occurredAt: new \DateTimeImmutable(),
            rawPayload: $request->payload,
        );
    }

    public function capture(PaymentCaptureRequest $request): PaymentWebhookResult
    {
        $this->denyUnlessConfigured();
        $token = $this->accessToken();

        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/v2/checkout/orders/' . $request->providerTransactionId . '/capture', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
        ]);
        $data = $response->toArray(false);
        $status = is_string($data['status'] ?? null) && strtoupper($data['status']) === 'COMPLETED'
            ? PaymentProviderStatus::Paid
            : PaymentProviderStatus::AwaitingConfirmation;

        return new PaymentWebhookResult(
            providerCode: self::CODE,
            providerTransactionId: $request->providerTransactionId,
            status: $status,
            occurredAt: new \DateTimeImmutable(),
            rawPayload: $data,
        );
    }

    private function accessToken(): string
    {
        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/v1/oauth2/token', [
            'auth_basic' => [$this->config->clientId, $this->config->clientSecret],
            'body' => ['grant_type' => 'client_credentials'],
        ]);
        $token = $response->toArray(false)['access_token'] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new InvalidDataException('Nie udało się uzyskać tokenu OAuth2 PayPal.');
        }

        return $token;
    }

    private function verifySignature(PaymentWebhookRequest $request): void
    {
        $token = $this->accessToken();
        $body = [
            'webhook_id' => $this->config->webhookId,
            'transmission_id' => $request->headers['paypal-transmission-id'] ?? '',
            'transmission_time' => $request->headers['paypal-transmission-time'] ?? '',
            'cert_url' => $request->headers['paypal-cert-url'] ?? '',
            'auth_algo' => $request->headers['paypal-auth-algo'] ?? '',
            'transmission_sig' => $request->headers['paypal-transmission-sig'] ?? '',
            'webhook_event' => $request->payload,
        ];
        $response = $this->httpClient->request('POST', $this->config->normalizedBaseUrl() . '/v1/notifications/verify-webhook-signature', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json' => $body,
        ]);
        $status = $response->toArray(false)['verification_status'] ?? null;
        if ($status !== 'SUCCESS') {
            throw new InvalidDataException('Niepoprawny podpis webhooka PayPal.');
        }
    }

    /** @param array<string, mixed> $data */
    private function approveUrl(array $data): ?string
    {
        $links = $data['links'] ?? [];
        if (is_array($links)) {
            foreach ($links as $link) {
                if (is_array($link) && ($link['rel'] ?? null) === 'approve' && is_string($link['href'] ?? null)) {
                    return $link['href'];
                }
            }
        }

        return null;
    }

    private function mapStatus(string $eventType): PaymentProviderStatus
    {
        return match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.COMPLETED' => PaymentProviderStatus::Paid,
            'CHECKOUT.ORDER.APPROVED' => PaymentProviderStatus::AwaitingConfirmation,
            'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' => PaymentProviderStatus::Failed,
            'PAYMENT.CAPTURE.REFUNDED' => PaymentProviderStatus::Refunded,
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

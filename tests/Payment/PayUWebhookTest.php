<?php

declare(strict_types=1);

namespace Core\Tests\Payment;

use Core\Exception\InvalidDataException;
use Core\Payment\Dto\PaymentWebhookRequest;
use Core\Payment\Enum\PaymentProviderStatus;
use Core\Payment\Provider\PayUConfig;
use Core\Payment\Provider\PayUPaymentProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * Webhook PayU: host musi dostać kwotę z podpisanego powiadomienia, aby porównać ją z oczekiwaną,
 * oraz rozpoznać zakończony zwrot (audyt Pondus 2026-10-25 AUD-003).
 */
final class PayUWebhookTest extends TestCase
{
    private const SECOND_KEY = 'second-key';

    private function provider(): PayUPaymentProvider
    {
        return new PayUPaymentProvider(new PayUConfig('client', 'secret', '145227', self::SECOND_KEY), new MockHttpClient());
    }

    /** @param array<string, mixed> $payload */
    private function request(array $payload, ?string $signature = null): PaymentWebhookRequest
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature ??= md5($raw . self::SECOND_KEY);

        return new PaymentWebhookRequest('payu', ['openpayu-signature' => 'sender=checkout;signature=' . $signature . ';algorithm=MD5'], $payload, $raw);
    }

    public function testCompletedOrderReportsPaidAmountFromNotification(): void
    {
        $result = $this->provider()->handleWebhook($this->request([
            'order' => ['orderId' => 'ORD1', 'status' => 'COMPLETED', 'totalAmount' => '4900', 'currencyCode' => 'PLN'],
        ]));

        self::assertSame(PaymentProviderStatus::Paid, $result->status);
        self::assertSame('ORD1', $result->providerTransactionId);
        self::assertSame(4900, $result->paidMoney?->minorUnits());
        self::assertSame('PLN', $result->paidMoney?->currency);
    }

    public function testMissingAmountLeavesPaidMoneyEmpty(): void
    {
        $result = $this->provider()->handleWebhook($this->request(['order' => ['orderId' => 'ORD1', 'status' => 'COMPLETED']]));

        self::assertNull($result->paidMoney);
    }

    public function testFinalizedRefundMapsToRefundedWithRefundAmount(): void
    {
        $result = $this->provider()->handleWebhook($this->request([
            'orderId' => 'ORD1',
            'refund' => ['refundId' => 'R1', 'amount' => '4900', 'currencyCode' => 'PLN', 'status' => 'FINALIZED'],
        ]));

        self::assertSame(PaymentProviderStatus::Refunded, $result->status);
        self::assertSame('ORD1', $result->providerTransactionId);
        self::assertSame(4900, $result->paidMoney?->minorUnits());
    }

    public function testPendingRefundIsNotFinal(): void
    {
        $result = $this->provider()->handleWebhook($this->request([
            'orderId' => 'ORD1',
            'refund' => ['refundId' => 'R1', 'amount' => '4900', 'currencyCode' => 'PLN', 'status' => 'PENDING'],
        ]));

        self::assertSame(PaymentProviderStatus::Pending, $result->status);
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->provider()->handleWebhook($this->request(['order' => ['orderId' => 'ORD1', 'status' => 'COMPLETED']], 'bad'));
    }
}

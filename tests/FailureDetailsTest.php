<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Observability\FailureDetails;
use PHPUnit\Framework\TestCase;

final class FailureDetailsTest extends TestCase
{
    public function testExceptionStructureIsRetainedWithoutArgumentsOrRawMessages(): void
    {
        $previous = new \RuntimeException('535 authentication secret-marker');
        $error = new \RuntimeException('SQLSTATE[42703] undefined column secret-marker', 0, $previous);
        $details = FailureDetails::from($error);

        self::assertSame('database_schema', $details['error_category']);
        self::assertSame('42703', $details['error_code']);
        self::assertCount(2, $details['causes']);
        self::assertSame(\RuntimeException::class, $details['exception_class']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $details['error_fingerprint']);
        self::assertStringNotContainsString('secret-marker', json_encode($details, JSON_THROW_ON_ERROR));
        self::assertArrayNotHasKey('args', $details['safe_stack'][0] ?? []);
    }

    public function testCategoriesNameTheLikelyCauseWithoutTheMessage(): void
    {
        self::assertSame('smtp_auth', FailureDetails::from(new \RuntimeException('535 5.7.8 Authentication failed'))['error_category']);
        self::assertSame('timeout', FailureDetails::from(new \RuntimeException('Operation timed out after 10 s'))['error_category']);
        self::assertSame('application', FailureDetails::from(new \LogicException('Something else'))['error_category']);
    }
}

<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Command\DeploymentCheckCommand;
use Core\Deployment\DeploymentCheckProviderInterface;
use Core\Deployment\DeploymentCheckRunner;
use Core\Deployment\DeploymentReportMailerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DeploymentCheckRunnerTest extends TestCase
{
    public function testAnIndividualFailureDoesNotHideTheOtherChecksOrLeakMessages(): void
    {
        $provider = new class implements DeploymentCheckProviderInterface {
            public function checks(): iterable
            {
                yield 'database' => static fn (): array => throw new \RuntimeException('SQLSTATE[42703] undefined column secret-marker');
                yield 'assets' => static fn (): array => ['status' => 'pass', 'detail' => 'Assets present.'];
            }
        };
        $report = (new DeploymentCheckRunner([$provider]))->run();
        self::assertFalse($report['ok']);
        self::assertSame('fail', $report['checks']['database']['status']);
        self::assertSame('pass', $report['checks']['assets']['status']);
        self::assertSame('42703', $report['checks']['database']['data']['error_code']);
        self::assertStringNotContainsString('secret-marker', json_encode($report, JSON_THROW_ON_ERROR));
    }

    public function testConsoleReportIsWrittenBeforeFailingMailAndNoEmailSkipsTheMailer(): void
    {
        $provider = new class implements DeploymentCheckProviderInterface {
            public function checks(): iterable { yield 'example' => static fn (): array => ['status' => 'pass', 'detail' => 'OK']; }
        };
        $mailer = $this->createMock(DeploymentReportMailerInterface::class);
        $mailer->expects(self::once())->method('send')->willThrowException(new \RuntimeException('535 authenticate secret-marker'));
        $tester = new CommandTester(new DeploymentCheckCommand(new DeploymentCheckRunner([$provider]), $mailer));
        self::assertSame(1, $tester->execute(['--json' => true]));
        $lines = array_map(static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR), explode("\n", trim($tester->getDisplay())));
        self::assertSame('deployment_report', $lines[0]['type']);
        self::assertSame('report_delivery', $lines[1]['type']);
        self::assertSame('fail', $lines[1]['status']);
        self::assertSame($lines[0]['run_id'], $lines[1]['run_id']);
        self::assertStringNotContainsString('secret-marker', $tester->getDisplay());
        self::assertSame(0, $tester->execute(['--no-email' => true, '--json' => true]));
    }
}

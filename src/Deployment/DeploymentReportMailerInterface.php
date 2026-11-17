<?php

declare(strict_types=1);

namespace Core\Deployment;

interface DeploymentReportMailerInterface
{
    /** @param array<string,mixed> $report */
    public function send(array $report, ?string $recipient): void;
}

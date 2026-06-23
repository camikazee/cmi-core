<?php

declare(strict_types=1);

namespace Core\Audit\Contract;

use Core\Audit\Model\AuditLogRecord;

interface AuditEventPublisherInterface
{
    public function publish(AuditLogRecord $record): void;
}


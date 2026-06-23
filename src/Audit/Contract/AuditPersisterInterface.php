<?php

declare(strict_types=1);

namespace Core\Audit\Contract;

use Core\Audit\Model\AuditLogRecord;

interface AuditPersisterInterface
{
    public function persist(AuditLogRecord $record): void;
}


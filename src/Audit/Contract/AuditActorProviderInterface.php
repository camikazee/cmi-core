<?php

declare(strict_types=1);

namespace Core\Audit\Contract;

interface AuditActorProviderInterface
{
    public function getCurrentActorId(): ?string;
}


<?php

declare(strict_types=1);

namespace Core\Notification\Contract;

interface TemplateResolverInterface
{
    public function findTemplate(
        string $key,
        string $channel = 'email',
        ?string $locale = null,
        ?int $triggerDaysBefore = null
    ): ?NotificationTemplateInterface;

    /**
     * @return array<int,NotificationTemplateInterface>
     */
    public function getExpiringSubscriptionTemplates(string $channel = 'email', ?string $locale = null): array;
}


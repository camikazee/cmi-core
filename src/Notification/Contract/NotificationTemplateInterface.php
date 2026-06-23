<?php

declare(strict_types=1);

namespace Core\Notification\Contract;

interface NotificationTemplateInterface
{
    public function getTemplateKey(): string;

    public function getChannel(): string;

    public function getLocale(): ?string;

    public function getSubject(): ?string;

    public function getBodyText(): ?string;

    public function getBodyHtml(): ?string;

    public function getTriggerDaysBefore(): ?int;

    public function isActive(): bool;
}


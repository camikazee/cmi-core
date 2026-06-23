<?php

declare(strict_types=1);

namespace Core\Notification\Model;

use Core\Notification\Contract\NotificationTemplateInterface;

final class FileNotificationTemplate implements NotificationTemplateInterface
{
    public function __construct(
        private readonly string $templateKey,
        private readonly string $channel,
        private readonly string $locale,
        private readonly ?string $subject,
        private readonly ?string $bodyText,
        private readonly ?string $bodyHtml,
    ) {
    }

    public function getTemplateKey(): string { return $this->templateKey; }
    public function getChannel(): string { return $this->channel; }
    public function getLocale(): ?string { return $this->locale; }
    public function getSubject(): ?string { return $this->subject; }
    public function getBodyText(): ?string { return $this->bodyText; }
    public function getBodyHtml(): ?string { return $this->bodyHtml; }
    public function getTriggerDaysBefore(): ?int { return null; }
    public function isActive(): bool { return true; }
}

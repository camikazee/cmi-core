<?php

declare(strict_types=1);

namespace Core\Notification\Service;

use Core\Notification\Contract\NotificationTemplateInterface;
use Twig\Environment;

final class TemplateRenderer
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function renderSubject(NotificationTemplateInterface $template, array $context): ?string
    {
        $subject = $template->getSubject();
        if ($subject === null || trim($subject) === '') {
            return null;
        }

        return trim($this->twig->createTemplate($subject)->render($context));
    }

    /**
     * @param array<string,mixed> $context
     */
    public function renderBodyText(NotificationTemplateInterface $template, array $context): ?string
    {
        $body = $template->getBodyText();
        if ($body === null || trim($body) === '') {
            return null;
        }

        return trim($this->twig->createTemplate($body)->render($context));
    }

    /**
     * @param array<string,mixed> $context
     */
    public function renderBodyHtml(NotificationTemplateInterface $template, array $context): ?string
    {
        $html = $template->getBodyHtml();
        if ($html === null || trim($html) === '') {
            return null;
        }

        return $this->twig->createTemplate($html)->render($context);
    }
}


<?php

declare(strict_types=1);

namespace Core\Notification\Service;

use Core\Notification\Contract\NotificationTemplateInterface;
use Core\Notification\Model\FileNotificationTemplate;
use Symfony\Component\Yaml\Yaml;

/**
 * Resolves email templates from YAML files.
 *
 * Resolution order per locale:
 *   1. $projectDir/{channel}/{key-path}/{locale}.yaml  (project customisation)
 *   2. $coreDir/{channel}/{key-path}/{locale}.yaml     (core defaults shipped with package)
 * Then retries with locale='default' as fallback.
 *
 * Template key is mapped to a directory path by replacing '.' with '/':
 *   auth.activation_code → auth/activation_code
 *
 * $coreDir defaults to the package's own resources/templates directory — this works
 * correctly whether the package is loaded via path repository or installed in vendor/.
 */
final class FileTemplateLoader
{
    private readonly string $coreDir;

    public function __construct(
        ?string $coreDir = null,
        private readonly string $projectDir = '',
    ) {
        // Resolves to libs/Core/resources/templates regardless of install location.
        // __DIR__ = .../src/Notification/Service/ → 3 levels up = package root
        $this->coreDir = $coreDir ?? dirname(__DIR__, 3) . '/resources/templates';
    }

    public function find(string $key, string $channel, ?string $locale): ?NotificationTemplateInterface
    {
        $relativePath = str_replace('.', '/', $key);

        // Try requested locale first, then 'default' as fallback
        $localesToTry = array_unique(array_filter([$locale, 'default']));

        foreach ($localesToTry as $loc) {
            // Project overrides take priority over core defaults
            if ($this->projectDir !== '') {
                $template = $this->load($this->projectDir, $channel, $relativePath, $loc, $key, $locale ?? $loc);
                if ($template !== null) {
                    return $template;
                }
            }

            $template = $this->load($this->coreDir, $channel, $relativePath, $loc, $key, $locale ?? $loc);
            if ($template !== null) {
                return $template;
            }
        }

        return null;
    }

    private function load(
        string $baseDir,
        string $channel,
        string $path,
        string $locale,
        string $key,
        string $resolvedLocale,
    ): ?NotificationTemplateInterface {
        $file = sprintf('%s/%s/%s/%s.yaml', rtrim($baseDir, '/'), $channel, $path, $locale);
        if (!is_file($file)) {
            return null;
        }

        $data = Yaml::parseFile($file);
        if (!is_array($data)) {
            return null;
        }

        return new FileNotificationTemplate(
            templateKey: $key,
            channel: $channel,
            locale: $resolvedLocale,
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            bodyText: isset($data['bodyText']) ? (string) $data['bodyText'] : null,
            bodyHtml: isset($data['bodyHtml']) ? (string) $data['bodyHtml'] : null,
        );
    }
}

<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Notification\Service\FileTemplateLoader;
use PHPUnit\Framework\TestCase;

final class FileTemplateLoaderTest extends TestCase
{
    public function testProjectTemplateOverridesCoreTemplate(): void
    {
        $base = sys_get_temp_dir() . '/core-template-test-' . bin2hex(random_bytes(4));
        $project = $base . '/project';
        $core = $base . '/core';
        mkdir($project . '/email/auth/welcome', 0777, true);
        mkdir($core . '/email/auth/welcome', 0777, true);
        file_put_contents($core . '/email/auth/welcome/pl.yaml', "subject: Core\nbodyText: Core body\n");
        file_put_contents($project . '/email/auth/welcome/pl.yaml', "subject: Project\nbodyText: Project body\n");

        $template = (new FileTemplateLoader($core, $project))->find('auth.welcome', 'email', 'pl');

        self::assertNotNull($template);
        self::assertSame('Project', $template->getSubject());
        self::assertSame('Project body', $template->getBodyText());
    }
}

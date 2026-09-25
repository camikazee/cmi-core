<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\DependencyInjection\CoreExtension;
use Core\Scheduler\SchedulerRunListenerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CoreExtensionTest extends TestCase
{
    public function testHostRunListenersAreAutoconfigured(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        (new CoreExtension())->load([], $container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(SchedulerRunListenerInterface::class, $autoconfigured);
        self::assertArrayHasKey('core.scheduler.run_listener', $autoconfigured[SchedulerRunListenerInterface::class]->getTags());
    }
}

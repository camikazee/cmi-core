<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Audit\Contract\AuditPersisterInterface;
use Core\Audit\Service\AuditLogger;
use Core\Command\SeedDictionariesCommand;
use Core\DependencyInjection\Compiler\OptionalHostContractsPass;
use Core\Dictionary\DictionarySeederRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class OptionalHostContractsPassTest extends TestCase
{
    public function testRemovesFeaturesWhoseHostContractIsMissing(): void
    {
        $container = $this->containerWithFeatureServices();

        (new OptionalHostContractsPass())->process($container);

        self::assertFalse($container->has(AuditLogger::class));
        self::assertFalse($container->has(DictionarySeederRunner::class));
        self::assertFalse($container->has(SeedDictionariesCommand::class));
    }

    public function testKeepsFeaturesWhoseHostContractIsBound(): void
    {
        $container = $this->containerWithFeatureServices();
        $container->setDefinition('app.audit_persister', new Definition(InMemoryAuditPersister::class));
        $container->setAlias(AuditPersisterInterface::class, 'app.audit_persister');

        (new OptionalHostContractsPass())->process($container);

        self::assertTrue($container->has(AuditLogger::class));
        self::assertFalse($container->has(DictionarySeederRunner::class));
    }

    private function containerWithFeatureServices(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        foreach (OptionalHostContractsPass::FEATURES as $services) {
            foreach ($services as $service) {
                $container->setDefinition($service, new Definition($service));
            }
        }

        return $container;
    }
}

<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Audit\Contract\AuditPersisterInterface;
use Core\Audit\Doctrine\EntityChangeAuditListener;
use Core\Audit\Service\AuditLogger;
use Core\Auth\Service\AuthTokenService;
use Core\Auth\TokenIssuerInterface;
use Core\Command\SeedDictionariesCommand;
use Core\Dictionary\DictionaryPersisterInterface;
use Core\Dictionary\DictionarySeederRunner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Features that need a host adapter are opt-in. When the host does not bind the contract, the services
 * that depend on it are removed, so a project using only part of the package still compiles and optional
 * consumers (for example SchedulerRunner and its AuditLogger) receive null. Once a contract is bound,
 * every other dependency of the feature is required and misconfiguration stays visible.
 */
final class OptionalHostContractsPass implements CompilerPassInterface
{
    /** @var array<class-string, list<class-string>> */
    public const FEATURES = [
        AuditPersisterInterface::class => [AuditLogger::class, EntityChangeAuditListener::class],
        DictionaryPersisterInterface::class => [DictionarySeederRunner::class, SeedDictionariesCommand::class],
        TokenIssuerInterface::class => [AuthTokenService::class],
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::FEATURES as $contract => $services) {
            if ($container->has($contract)) {
                continue;
            }

            foreach ($services as $service) {
                $container->removeDefinition($service);
            }
        }
    }
}

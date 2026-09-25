<?php

declare(strict_types=1);

namespace Core;

use Core\DependencyInjection\Compiler\OptionalHostContractsPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class CoreBundle.
 */
class CoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new OptionalHostContractsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
    }
}

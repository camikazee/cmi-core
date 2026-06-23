<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class CoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yaml');

        $defaults = $container->getParameter('core');
        if (!is_array($defaults)) {
            $defaults = [];
        }

        $config = array_replace_recursive($defaults, $this->processConfiguration(new Configuration(), $configs));
        $this->setParameters($container, 'core', $config);
        $container->getParameterBag()->remove('core');

        $loader->load('services.yaml');

        if (in_array((string) $container->getParameter('kernel.environment'), ['dev', 'test'], true)) {
            $loader->load('services_dev.yaml');
        }
    }

    /**
     * Ustawia parametr pod ścieżką "core.x" oraz — dla węzłów asocjacyjnych — rekurencyjnie
     * spłaszcza je do parametrów "core.x.y.z", aby usługi mogły referować konkretne pola.
     */
    private function setParameters(ContainerBuilder $container, string $prefix, mixed $value): void
    {
        $container->setParameter($prefix, $value);
        if (is_array($value) && $value !== [] && !array_is_list($value)) {
            foreach ($value as $key => $child) {
                $this->setParameters($container, sprintf('%s.%s', $prefix, $key), $child);
            }
        }
    }
}

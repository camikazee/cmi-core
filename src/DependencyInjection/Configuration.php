<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('core');

        $builder->getRootNode()
            ->children()
                ->arrayNode('mailer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('from_email')->defaultNull()->end()
                        ->scalarNode('from_name')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('project_dir')->defaultValue('%kernel.project_dir%/templates/email')->end()
                    ->end()
                ->end()
                ->arrayNode('payment')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('active_provider')->defaultValue('manual')->end()
                        ->arrayNode('przelewy24')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('merchant_id')->defaultValue(0)->end()
                                ->integerNode('pos_id')->defaultValue(0)->end()
                                ->scalarNode('api_key')->defaultValue('')->end()
                                ->scalarNode('crc')->defaultValue('')->end()
                                ->scalarNode('base_url')->defaultValue('https://sandbox.przelewy24.pl')->end()
                            ->end()
                        ->end()
                        ->arrayNode('payu')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('client_id')->defaultValue('')->end()
                                ->scalarNode('client_secret')->defaultValue('')->end()
                                ->scalarNode('merchant_pos_id')->defaultValue('')->end()
                                ->scalarNode('second_key')->defaultValue('')->end()
                                ->scalarNode('base_url')->defaultValue('https://secure.snd.payu.com')->end()
                            ->end()
                        ->end()
                        ->arrayNode('paypal')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('client_id')->defaultValue('')->end()
                                ->scalarNode('client_secret')->defaultValue('')->end()
                                ->scalarNode('webhook_id')->defaultValue('')->end()
                                ->scalarNode('base_url')->defaultValue('https://api-m.sandbox.paypal.com')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}

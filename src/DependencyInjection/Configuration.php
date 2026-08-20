<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vt_innovations_simple_notify');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('log')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Record every send attempt in tl_simple_log.')
                        ->end()
                        ->booleanNode('store_body')
                            ->defaultTrue()
                            ->info('Store the rendered subject, body and token values. Required for resending and for previewing what a recipient received. Turn off on sites that must not retain personal data in the log.')
                        ->end()
                        ->integerNode('retention_days')
                            ->defaultValue(90)
                            ->min(0)
                            ->info('Delete log entries older than this many days. 0 keeps them forever.')
                        ->end()
                        ->integerNode('max_attempts')
                            ->defaultValue(3)
                            ->min(1)
                            ->info('How many times the retry cron job re-attempts a failed message before giving up.')
                        ->end()
                        ->booleanNode('retry_failed')
                            ->defaultTrue()
                            ->info('Automatically re-attempt failed messages once an hour.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

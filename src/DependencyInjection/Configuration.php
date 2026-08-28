<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

declare(strict_types=1);

namespace VTInnovations\CentralizedNotificationSuite\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('centralized_notification_suite');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('mailer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('php_binary')
                            ->defaultValue('')
                            ->info('Path to the PHP CLI binary used to rebuild the cache after the SMTP settings change. Empty means auto-detect.')
                        ->end()
                        ->integerNode('process_timeout')
                            ->defaultValue(120)
                            ->min(30)
                            ->info('Seconds before the cache rebuild subprocess is abandoned.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('log')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Record every send attempt in tl_notification_log.')
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

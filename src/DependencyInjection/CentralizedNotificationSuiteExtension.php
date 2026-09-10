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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CentralizedNotificationSuiteExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('centralized_notification_suite.mailer.php_binary', $config['mailer']['php_binary']);
        $container->setParameter('centralized_notification_suite.mailer.process_timeout', $config['mailer']['process_timeout']);
        $container->setParameter('centralized_notification_suite.mailer.memory_limit', (string) $config['mailer']['memory_limit']);
        $container->setParameter('centralized_notification_suite.log.enabled', $config['log']['enabled']);
        $container->setParameter('centralized_notification_suite.log.store_body', $config['log']['store_body']);
        $container->setParameter('centralized_notification_suite.log.retention_days', $config['log']['retention_days']);
        $container->setParameter('centralized_notification_suite.log.max_attempts', $config['log']['max_attempts']);
        $container->setParameter('centralized_notification_suite.log.retry_failed', $config['log']['retry_failed']);
    }
}

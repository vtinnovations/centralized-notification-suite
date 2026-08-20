<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class VTInnovationsSimpleNotifyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('vt_innovations_simple_notify.log.enabled', $config['log']['enabled']);
        $container->setParameter('vt_innovations_simple_notify.log.store_body', $config['log']['store_body']);
        $container->setParameter('vt_innovations_simple_notify.log.retention_days', $config['log']['retention_days']);
        $container->setParameter('vt_innovations_simple_notify.log.max_attempts', $config['log']['max_attempts']);
        $container->setParameter('vt_innovations_simple_notify.log.retry_failed', $config['log']['retry_failed']);
    }
}

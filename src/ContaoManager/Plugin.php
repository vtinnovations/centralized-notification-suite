<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use VTInnovations\SimpleNotifyBundle\VTInnovationsSimpleNotifyBundle;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(VTInnovationsSimpleNotifyBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * Loads the #[Route] attributes on the backend controllers (message preview and test
     * send). Contao does not scan bundle controllers by itself, so without this the routes
     * simply would not exist.
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        return $resolver
            ->resolve(\dirname(__DIR__).'/Controller', 'attribute')
            ?->load(\dirname(__DIR__).'/Controller', 'attribute')
        ;
    }
}

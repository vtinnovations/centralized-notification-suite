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

namespace VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Symfony\Component\HttpFoundation\RequestStack;
use VTInnovations\CentralizedNotificationSuite\Model\BrandingModel;

/**
 * Makes the branding module open its single record directly.
 *
 * There is one brand per site, so a list holding exactly one row is a pointless click. The
 * record is created on first visit rather than by a migration, because a migration that
 * writes content would run on every site whether the module is ever opened or not.
 *
 * Why a loadDataContainer hook and not an onload callback: DC_Table reads Input::get('id')
 * in the first lines of its constructor, long before it runs config.onload_callback, so an
 * onload callback can switch the action but never supply the id -- which produced
 * "Cannot load record tl_notification_branding.id=". Backend::getBackendModule() calls
 * loadDataContainer() *before* constructing the data container, so this is the last point
 * where both can still be set.
 *
 * And why not a redirect: the redirect target is the module URL itself, which lands back
 * here and redirects again. That is what made "Go back" appear to do nothing, since it
 * targets the list view.
 */
#[AsHook('loadDataContainer')]
class BrandingListener
{
    private const TABLE = 'tl_notification_branding';

    private const MODULE = 'notification_branding';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(string $table): void
    {
        if (self::TABLE !== $table) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // The hook also fires when Contao loads the DCA to build the database schema, where
        // there is no request and creating a record would be wrong.
        if (!$request || self::MODULE !== $request->query->get('do')) {
            return;
        }

        // Already editing, restoring a version or running a select action: leave it alone
        if ($request->query->get('act')) {
            return;
        }

        $this->framework->initialize();

        BrandingModel::findSingleOrCreate();

        Input::setGet('act', 'edit');
        Input::setGet('id', BrandingModel::ID);
    }
}

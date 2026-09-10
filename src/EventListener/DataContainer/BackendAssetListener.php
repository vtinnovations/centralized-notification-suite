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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Loads the one backend asset this product ships, on this product's screens only.
 *
 * The asset hands the global "$" back to MooTools when another extension has loaded jQuery over
 * it. Without that, Contao's file picker never binds -- the "Change selection" link navigates to
 * the file manager as a whole page instead of opening the modal, so the Apply and Cancel buttons
 * do not exist and no image can be chosen. It is the same failure for the Logo field on
 * Branding, the Image field on a block and the Attachments field on a message.
 *
 * Why a loadDataContainer hook rather than a kernel listener: the fix has to be narrow. A
 * listener would put it on every backend page, including the screens of the very extension that
 * wants "$" to be jQuery, and break it. Keyed to our own tables, the asset never appears on a
 * page that is not ours.
 *
 * tl_settings is deliberately not in the list even though this product has a section there. It
 * is a shared screen: other products render their own fields on it, and taking "$" away from
 * them there would be exactly the overreach this list avoids.
 *
 * Why appended rather than prepended: the asset must run after whichever extension loaded
 * jQuery. Legacy modules register their assets while the configuration is read, long before any
 * data container is loaded, so appending here puts this last in the head -- after their jQuery
 * and before the body, where the widgets' inline scripts run.
 */
#[AsHook('loadDataContainer')]
class BackendAssetListener
{
    /**
     * This product's own editing screens. Nothing else gets the asset.
     */
    private const TABLES = [
        'tl_notification',
        'tl_notification_message',
        'tl_notification_block',
        'tl_notification_template',
        'tl_notification_branding',
        'tl_notification_gateway',
        'tl_notification_log',
    ];

    private const ASSET = 'bundles/centralizednotificationsuite/js/mootools-dollar.js';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(string $table): void
    {
        if (!\in_array($table, self::TABLES, true)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // The hook also fires with no request at all (schema updates on the command line) and
        // in the front end, where these tables are read to send a notification. Neither renders
        // a backend screen, and the front end must not be handed a backend asset.
        if (null === $request || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        // A fixed key, so several of our tables loading on one screen add it once.
        $GLOBALS['TL_JAVASCRIPT']['cns_mootools_dollar'] = self::ASSET.'|static';
    }
}

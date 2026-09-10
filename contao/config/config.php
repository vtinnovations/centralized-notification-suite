<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

use VTInnovations\CentralizedNotificationSuite\Controller\Backend\SmtpConfigModule;
use VTInnovations\CentralizedNotificationSuite\Model\BlockModel;
use VTInnovations\CentralizedNotificationSuite\Model\BrandingModel;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;
use VTInnovations\CentralizedNotificationSuite\Model\LogModel;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Widget\ActivationPanel;
use VTInnovations\CentralizedNotificationSuite\Model\TemplateModel;

/*
 * Own backend group rather than entries under SYSTEM: the modules belong together, are
 * used by editors rather than administrators, and would otherwise sit next to (and be
 * indistinguishable from) Notification Center's identically named modules.
 */
$notificationGroup = [
    'notification' => [
        'tables' => ['tl_notification', 'tl_notification_message', 'tl_notification_block'],
    ],
    'notification_template' => [
        'tables' => ['tl_notification_template'],
    ],
    'notification_branding' => [
        'tables' => ['tl_notification_branding'],
    ],
    'notification_gateway' => [
        'tables' => ['tl_notification_gateway'],
    ],
    'notification_log' => [
        'tables' => ['tl_notification_log'],
    ],
    // A callback module, not a table: the SMTP settings live in .env.local, because
    // MAILER_DSN has to be readable before the container is built.
    'notification_mailer' => [
        'callback' => SmtpConfigModule::class,
    ],
];

// Place the group directly before SYSTEM instead of appending it after, so it sits with
// the content modules an editor works in rather than at the very bottom of the menu.
$position = array_search('system', array_keys($GLOBALS['BE_MOD']), true);

if (false === $position) {
    $GLOBALS['BE_MOD']['centralized_notification_suite'] = $notificationGroup;
} else {
    $GLOBALS['BE_MOD'] = array_merge(
        \array_slice($GLOBALS['BE_MOD'], 0, $position, true),
        ['centralized_notification_suite' => $notificationGroup],
        \array_slice($GLOBALS['BE_MOD'], $position, null, true),
    );
}

// The activation panel rendered inside Contao's own settings form
$GLOBALS['BE_FFL']['cnsActivationPanel'] = ActivationPanel::class;

$GLOBALS['TL_MODELS']['tl_notification'] = NotificationModel::class;
$GLOBALS['TL_MODELS']['tl_notification_gateway'] = GatewayModel::class;
$GLOBALS['TL_MODELS']['tl_notification_message'] = MessageModel::class;
$GLOBALS['TL_MODELS']['tl_notification_log'] = LogModel::class;
$GLOBALS['TL_MODELS']['tl_notification_template'] = TemplateModel::class;
$GLOBALS['TL_MODELS']['tl_notification_branding'] = BrandingModel::class;
$GLOBALS['TL_MODELS']['tl_notification_block'] = BlockModel::class;

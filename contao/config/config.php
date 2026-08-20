<?php

use VTInnovations\SimpleNotifyBundle\Model\GatewayModel;
use VTInnovations\SimpleNotifyBundle\Model\LogModel;
use VTInnovations\SimpleNotifyBundle\Model\MessageModel;
use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;
use VTInnovations\SimpleNotifyBundle\Model\TemplateModel;

/*
 * Own backend group rather than entries under SYSTEM: the modules belong together, are
 * used by editors rather than administrators, and would otherwise sit next to (and be
 * indistinguishable from) Notification Center's identically named modules.
 */
$notifyGroup = [
    'simple_notify' => [
        'tables' => ['tl_simple_notification', 'tl_simple_message'],
    ],
    'simple_notify_template' => [
        'tables' => ['tl_simple_template'],
    ],
    'simple_notify_gateway' => [
        'tables' => ['tl_simple_gateway'],
    ],
    'simple_notify_log' => [
        'tables' => ['tl_simple_log'],
    ],
];

// Place the group directly before SYSTEM instead of appending it after, so it sits with
// the content modules an editor works in rather than at the very bottom of the menu.
$position = array_search('system', array_keys($GLOBALS['BE_MOD']), true);

if (false === $position) {
    $GLOBALS['BE_MOD']['notify'] = $notifyGroup;
} else {
    $GLOBALS['BE_MOD'] = array_merge(
        \array_slice($GLOBALS['BE_MOD'], 0, $position, true),
        ['notify' => $notifyGroup],
        \array_slice($GLOBALS['BE_MOD'], $position, null, true),
    );
}

$GLOBALS['TL_MODELS']['tl_simple_notification'] = NotificationModel::class;
$GLOBALS['TL_MODELS']['tl_simple_gateway'] = GatewayModel::class;
$GLOBALS['TL_MODELS']['tl_simple_message'] = MessageModel::class;
$GLOBALS['TL_MODELS']['tl_simple_log'] = LogModel::class;
$GLOBALS['TL_MODELS']['tl_simple_template'] = TemplateModel::class;

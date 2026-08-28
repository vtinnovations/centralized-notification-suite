<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification']['title_legend'] = 'Title and alias';

$GLOBALS['TL_LANG']['tl_notification']['title'] = ['Title', 'Enter a name for this notification.'];
$GLOBALS['TL_LANG']['tl_notification']['alias'] = ['Alias', 'A unique alias. This is the string your code passes to CentralizedNotificationSuite::send(). Leave blank to generate it from the title.'];
$GLOBALS['TL_LANG']['tl_notification']['type'] = ['Triggered by', 'What sends this notification. The type decides which tokens the message editor offers you, and which notifications each trigger lists.'];

$GLOBALS['TL_LANG']['tl_notification']['type_options'] = [
    'form' => 'A form submission',
    'member' => 'A member action (registration, password reset, ...)',
    'comment' => 'A new comment',
    'newsletter' => 'A newsletter subscription',
    'custom' => 'Your own code',
];

$GLOBALS['TL_LANG']['tl_notification']['new'] = ['New notification', 'Create a new notification'];
$GLOBALS['TL_LANG']['tl_notification']['edit'] = ['Edit messages', 'Edit the messages of notification ID %s'];
$GLOBALS['TL_LANG']['tl_notification']['editheader'] = ['Edit settings', 'Edit the settings of notification ID %s'];
$GLOBALS['TL_LANG']['tl_notification']['copy'] = ['Copy notification', 'Copy notification ID %s'];
$GLOBALS['TL_LANG']['tl_notification']['delete'] = ['Delete notification', 'Delete notification ID %s'];
$GLOBALS['TL_LANG']['tl_notification']['show'] = ['Notification details', 'Show the details of notification ID %s'];

<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_log']['details_legend'] = 'Delivery';
$GLOBALS['TL_LANG']['tl_notification_log']['content_legend'] = 'What was sent';

$GLOBALS['TL_LANG']['tl_notification_log']['tstamp'] = ['Date', 'When the delivery was last attempted.'];
$GLOBALS['TL_LANG']['tl_notification_log']['alias'] = ['Notification', 'The alias of the notification that was triggered.'];
$GLOBALS['TL_LANG']['tl_notification_log']['gateway_type'] = ['Gateway type', 'The kind of gateway used.'];
$GLOBALS['TL_LANG']['tl_notification_log']['recipients'] = ['Recipients', 'The addresses the message was sent to.'];
$GLOBALS['TL_LANG']['tl_notification_log']['subject'] = ['Subject', 'The rendered subject line.'];
$GLOBALS['TL_LANG']['tl_notification_log']['status'] = ['Status', 'The outcome of the delivery.'];
$GLOBALS['TL_LANG']['tl_notification_log']['error'] = ['Error', 'Why the delivery failed.'];
$GLOBALS['TL_LANG']['tl_notification_log']['body_text'] = ['Plain text body', 'The plain text part as the recipient received it.'];
$GLOBALS['TL_LANG']['tl_notification_log']['body_html'] = ['HTML body', 'The HTML part as the recipient received it.'];
$GLOBALS['TL_LANG']['tl_notification_log']['source'] = ['Triggered by', 'What caused this notification to be sent.'];
$GLOBALS['TL_LANG']['tl_notification_log']['attempts'] = ['Attempts', 'How many times delivery has been attempted.'];

$GLOBALS['TL_LANG']['tl_notification_log']['status_options'] = [
    'pending' => 'Sending',
    'queued' => 'Queued',
    'sent' => 'Delivered',
    'failed' => 'Failed',
    'skipped' => 'Skipped',
];

$GLOBALS['TL_LANG']['tl_notification_log']['source_options'] = [
    'form' => 'Form submission',
    'api' => 'Code',
    'test' => 'Test send',
    'resend' => 'Manual resend',
    'cron' => 'Automatic retry',
];

$GLOBALS['TL_LANG']['tl_notification_log']['show'] = ['Show details', 'Show what was sent in entry ID %s'];
$GLOBALS['TL_LANG']['tl_notification_log']['delete'] = ['Delete entry', 'Delete log entry ID %s'];
$GLOBALS['TL_LANG']['tl_notification_log']['resend'] = ['Send again', 'Send this message again, exactly as it was'];
$GLOBALS['TL_LANG']['tl_notification_log']['clear'] = 'Clear the log';

$GLOBALS['TL_LANG']['tl_notification_log']['resendUnavailable'] = 'This entry cannot be sent again: its message body was not stored, or its gateway is gone.';
$GLOBALS['TL_LANG']['tl_notification_log']['resendOk'] = 'The message was sent again to %s.';
$GLOBALS['TL_LANG']['tl_notification_log']['resendFailed'] = 'Sending again failed: %s';
$GLOBALS['TL_LANG']['tl_notification_log']['resendImpossible'] = 'The message cannot be sent again because %s.';
$GLOBALS['TL_LANG']['tl_notification_log']['clearConfirm'] = 'Delete every entry in the send log?';
$GLOBALS['TL_LANG']['tl_notification_log']['clearOk'] = '%s log entries deleted.';

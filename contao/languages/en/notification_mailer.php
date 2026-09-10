<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['notification_mailer']['headline'] = 'SMTP Configuration';
$GLOBALS['TL_LANG']['notification_mailer']['back'] = 'Back';
$GLOBALS['TL_LANG']['notification_mailer']['access_denied'] = 'Access denied. Administrators only.';
$GLOBALS['TL_LANG']['notification_mailer']['intro'] = 'These are the SMTP credentials the whole site sends through, including every notification. A test e-mail must arrive before the settings are saved.';
$GLOBALS['TL_LANG']['notification_mailer']['active'] = 'Configured';
$GLOBALS['TL_LANG']['notification_mailer']['not_configured'] = 'Not configured';

$GLOBALS['TL_LANG']['notification_mailer']['server_section'] = 'Server';
$GLOBALS['TL_LANG']['notification_mailer']['smtp_host_label'] = 'SMTP host';
$GLOBALS['TL_LANG']['notification_mailer']['port_label'] = 'Port';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_label'] = 'Encryption';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_none'] = 'None';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_starttls'] = 'STARTTLS (usually port 587)';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_ssl'] = 'SSL/TLS (usually port 465)';
$GLOBALS['TL_LANG']['notification_mailer']['username_label'] = 'Username';
$GLOBALS['TL_LANG']['notification_mailer']['password_label'] = 'Password';
$GLOBALS['TL_LANG']['notification_mailer']['password_help'] = 'Leave blank to keep the password already saved.';

$GLOBALS['TL_LANG']['notification_mailer']['email_section'] = 'E-mail';
$GLOBALS['TL_LANG']['notification_mailer']['from_email_label'] = 'Sender address';
$GLOBALS['TL_LANG']['notification_mailer']['test_recipient_label'] = 'Test recipient';
$GLOBALS['TL_LANG']['notification_mailer']['test_recipient_help'] = 'A test e-mail is sent here before anything is saved.';
$GLOBALS['TL_LANG']['notification_mailer']['save_btn'] = 'Test and save';

$GLOBALS['TL_LANG']['notification_mailer']['error_host_required'] = 'The SMTP host is required.';
$GLOBALS['TL_LANG']['notification_mailer']['error_from_email_invalid'] = 'A valid sender address is required.';
$GLOBALS['TL_LANG']['notification_mailer']['error_test_recipient_invalid'] = 'A valid test recipient address is required.';
$GLOBALS['TL_LANG']['notification_mailer']['error_env_not_writable'] = 'The settings cannot be saved because the web server cannot write to %path%. Give it write access, then try again - nothing was sent.';
$GLOBALS['TL_LANG']['notification_mailer']['error_invalid_config'] = 'Invalid configuration: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['error_test_mail_failed'] = 'The test e-mail failed after %duration%s, so nothing was saved: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['error_save_failed'] = 'The test e-mail arrived, but .env.local could not be written: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['error_cache_clear_failed'] = 'The settings were saved but the cache could not be rebuilt, so they are not live yet. Clear the cache manually. Error: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['success_config_saved'] = 'Test e-mail delivered in %duration%s. Settings saved and cache rebuilt.';

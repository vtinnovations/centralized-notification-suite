<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['notification_mailer']['headline'] = 'SMTP-Konfiguration';
$GLOBALS['TL_LANG']['notification_mailer']['back'] = 'Zurück';
$GLOBALS['TL_LANG']['notification_mailer']['access_denied'] = 'Zugriff verweigert. Nur für Administratoren.';
$GLOBALS['TL_LANG']['notification_mailer']['intro'] = 'Über diese SMTP-Zugangsdaten versendet die gesamte Website, auch jede Benachrichtigung. Eine Test-E-Mail muss ankommen, bevor die Einstellungen gespeichert werden.';
$GLOBALS['TL_LANG']['notification_mailer']['active'] = 'Konfiguriert';
$GLOBALS['TL_LANG']['notification_mailer']['not_configured'] = 'Nicht konfiguriert';

$GLOBALS['TL_LANG']['notification_mailer']['server_section'] = 'Server';
$GLOBALS['TL_LANG']['notification_mailer']['smtp_host_label'] = 'SMTP-Host';
$GLOBALS['TL_LANG']['notification_mailer']['port_label'] = 'Port';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_label'] = 'Verschlüsselung';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_none'] = 'Keine';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_starttls'] = 'STARTTLS (meist Port 587)';
$GLOBALS['TL_LANG']['notification_mailer']['encryption_ssl'] = 'SSL/TLS (meist Port 465)';
$GLOBALS['TL_LANG']['notification_mailer']['username_label'] = 'Benutzername';
$GLOBALS['TL_LANG']['notification_mailer']['password_label'] = 'Passwort';
$GLOBALS['TL_LANG']['notification_mailer']['password_help'] = 'Leer lassen, um das gespeicherte Passwort beizubehalten.';

$GLOBALS['TL_LANG']['notification_mailer']['email_section'] = 'E-Mail';
$GLOBALS['TL_LANG']['notification_mailer']['from_email_label'] = 'Absenderadresse';
$GLOBALS['TL_LANG']['notification_mailer']['test_recipient_label'] = 'Testempfänger';
$GLOBALS['TL_LANG']['notification_mailer']['test_recipient_help'] = 'An diese Adresse wird vor dem Speichern eine Test-E-Mail gesendet.';
$GLOBALS['TL_LANG']['notification_mailer']['save_btn'] = 'Testen und speichern';

$GLOBALS['TL_LANG']['notification_mailer']['error_host_required'] = 'Der SMTP-Host ist erforderlich.';
$GLOBALS['TL_LANG']['notification_mailer']['error_from_email_invalid'] = 'Eine gültige Absenderadresse ist erforderlich.';
$GLOBALS['TL_LANG']['notification_mailer']['error_test_recipient_invalid'] = 'Eine gültige Empfängeradresse für den Test ist erforderlich.';
$GLOBALS['TL_LANG']['notification_mailer']['error_env_not_writable'] = 'Die Einstellungen können nicht gespeichert werden, weil der Webserver nicht in %path% schreiben darf. Schreibrechte erteilen und erneut versuchen - es wurde nichts gesendet.';
$GLOBALS['TL_LANG']['notification_mailer']['error_invalid_config'] = 'Ungültige Konfiguration: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['error_test_mail_failed'] = 'Die Test-E-Mail ist nach %duration%s fehlgeschlagen, es wurde nichts gespeichert: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['error_save_failed'] = 'Die Test-E-Mail kam an, aber .env.local konnte nicht geschrieben werden: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['error_cache_clear_failed'] = 'Die Einstellungen wurden gespeichert, der Cache konnte aber nicht neu aufgebaut werden – sie sind noch nicht aktiv. Bitte den Cache manuell leeren. Fehler: %error%';
$GLOBALS['TL_LANG']['notification_mailer']['success_config_saved'] = 'Test-E-Mail in %duration%s zugestellt. Einstellungen gespeichert, Cache neu aufgebaut.';

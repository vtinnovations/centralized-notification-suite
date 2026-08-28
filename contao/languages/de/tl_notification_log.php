<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_log']['details_legend'] = 'Versand';
$GLOBALS['TL_LANG']['tl_notification_log']['content_legend'] = 'Was gesendet wurde';

$GLOBALS['TL_LANG']['tl_notification_log']['tstamp'] = ['Datum', 'Wann der Versand zuletzt versucht wurde.'];
$GLOBALS['TL_LANG']['tl_notification_log']['alias'] = ['Benachrichtigung', 'Der Alias der ausgelösten Benachrichtigung.'];
$GLOBALS['TL_LANG']['tl_notification_log']['gateway_type'] = ['Absendertyp', 'Die Art des verwendeten Absenders.'];
$GLOBALS['TL_LANG']['tl_notification_log']['recipients'] = ['Empfänger', 'Die Adressen, an die gesendet wurde.'];
$GLOBALS['TL_LANG']['tl_notification_log']['subject'] = ['Betreff', 'Der aufgelöste Betreff.'];
$GLOBALS['TL_LANG']['tl_notification_log']['status'] = ['Status', 'Das Ergebnis des Versands.'];
$GLOBALS['TL_LANG']['tl_notification_log']['error'] = ['Fehler', 'Warum der Versand fehlgeschlagen ist.'];
$GLOBALS['TL_LANG']['tl_notification_log']['body_text'] = ['Nur-Text-Inhalt', 'Der Text-Teil, wie der Empfänger ihn erhalten hat.'];
$GLOBALS['TL_LANG']['tl_notification_log']['body_html'] = ['HTML-Inhalt', 'Der HTML-Teil, wie der Empfänger ihn erhalten hat.'];
$GLOBALS['TL_LANG']['tl_notification_log']['source'] = ['Ausgelöst durch', 'Was den Versand veranlasst hat.'];
$GLOBALS['TL_LANG']['tl_notification_log']['attempts'] = ['Versuche', 'Wie oft der Versand versucht wurde.'];

$GLOBALS['TL_LANG']['tl_notification_log']['status_options'] = [
    'pending' => 'Wird gesendet',
    'queued' => 'In Warteschlange',
    'sent' => 'Zugestellt',
    'failed' => 'Fehlgeschlagen',
    'skipped' => 'Übersprungen',
];

$GLOBALS['TL_LANG']['tl_notification_log']['source_options'] = [
    'form' => 'Formularversand',
    'api' => 'Code',
    'test' => 'Testversand',
    'resend' => 'Manuell erneut gesendet',
    'cron' => 'Automatischer Wiederholversuch',
];

$GLOBALS['TL_LANG']['tl_notification_log']['show'] = ['Details anzeigen', 'Anzeigen, was in Eintrag ID %s gesendet wurde'];
$GLOBALS['TL_LANG']['tl_notification_log']['delete'] = ['Eintrag löschen', 'Protokolleintrag ID %s löschen'];
$GLOBALS['TL_LANG']['tl_notification_log']['resend'] = ['Erneut senden', 'Diese Nachricht unverändert erneut senden'];
$GLOBALS['TL_LANG']['tl_notification_log']['clear'] = 'Protokoll leeren';

$GLOBALS['TL_LANG']['tl_notification_log']['resendUnavailable'] = 'Dieser Eintrag kann nicht erneut gesendet werden: der Inhalt wurde nicht gespeichert oder der Absender existiert nicht mehr.';
$GLOBALS['TL_LANG']['tl_notification_log']['resendOk'] = 'Die Nachricht wurde erneut an %s gesendet.';
$GLOBALS['TL_LANG']['tl_notification_log']['resendFailed'] = 'Das erneute Senden ist fehlgeschlagen: %s';
$GLOBALS['TL_LANG']['tl_notification_log']['resendImpossible'] = 'Die Nachricht kann nicht erneut gesendet werden, weil %s.';
$GLOBALS['TL_LANG']['tl_notification_log']['clearConfirm'] = 'Wirklich alle Einträge des Versandprotokolls löschen?';
$GLOBALS['TL_LANG']['tl_notification_log']['clearOk'] = '%s Protokolleinträge gelöscht.';

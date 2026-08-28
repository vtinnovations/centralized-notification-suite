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

$GLOBALS['TL_LANG']['tl_settings']['cns_licence_legend'] = 'Centralized Notification Suite Licence management';

$GLOBALS['TL_LANG']['tl_settings']['cns_panel'] = ['Lizenzstatus', 'Die aktuelle Aktivierung dieser Installation.'];
$GLOBALS['TL_LANG']['tl_settings']['cns_licence_key'] = ['Lizenzschlüssel', 'Den für diese Website ausgestellten Lizenzschlüssel eingeben und speichern. Der Schlüssel wird im signierten Lizenzdatensatz gespeichert, nicht in der Einstellungsdatei.'];

$GLOBALS['TL_LANG']['tl_settings']['cnsStatus'] = 'Status';
$GLOBALS['TL_LANG']['tl_settings']['cnsTier'] = 'Paket';
$GLOBALS['TL_LANG']['tl_settings']['cnsHost'] = 'Lizenzierter Host';
$GLOBALS['TL_LANG']['tl_settings']['cnsHosts'] = 'Abgedeckte Hosts';
$GLOBALS['TL_LANG']['tl_settings']['cnsVersion'] = 'Version';
$GLOBALS['TL_LANG']['tl_settings']['cnsTerm'] = 'Laufzeit';
$GLOBALS['TL_LANG']['tl_settings']['cnsPerpetual'] = 'Unbefristet';
$GLOBALS['TL_LANG']['tl_settings']['cnsDetail'] = 'Detail';
$GLOBALS['TL_LANG']['tl_settings']['cnsActive'] = 'Aktiv';
$GLOBALS['TL_LANG']['tl_settings']['cnsInactive'] = 'Nicht aktiviert';

$GLOBALS['TL_LANG']['tl_settings']['cnsRefresh'] = 'Lizenz aktualisieren';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemove'] = 'Lizenz entfernen';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoveConfirm'] = 'Die gespeicherte Lizenz entfernen? Es werden keine Benachrichtigungen mehr versendet, bis wieder eine Lizenz aktiviert ist.';

$GLOBALS['TL_LANG']['tl_settings']['cnsActivated'] = 'Die Lizenz wurde aktiviert.';
$GLOBALS['TL_LANG']['tl_settings']['cnsUpdated'] = 'Die Lizenz wurde aktualisiert.';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoved'] = 'Die Lizenz wurde entfernt.';
$GLOBALS['TL_LANG']['tl_settings']['cnsFailed'] = 'Die Lizenz konnte nicht verifiziert werden. Bitte den Schlüssel prüfen und erneut versuchen.';

$GLOBALS['TL_LANG']['tl_settings']['cnsReasonNone'] = 'Es wurde noch keine Lizenz aktiviert.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonHost'] = 'Für keine Startseite ist eine Domain konfiguriert, oder die Lizenz deckt keine konfigurierte Domain ab.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonExpired'] = 'Die gespeicherte Lizenz ist abgelaufen.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonFuture'] = 'Die gespeicherte Lizenz ist noch nicht gültig.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonGeneric'] = 'Die gespeicherte Lizenz konnte nicht verifiziert werden. Bitte erneut aktivieren.';

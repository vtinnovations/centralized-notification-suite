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

/*
 * Die Überschrift des gemeinsamen V-T.ONE-Abschnitts, in dem jedes V-T.ONE-Paket eine Karte
 * rendert. Mit ??= und nicht mit = zugewiesen: Die zuerst geladene Sprachdatei liefert den Text,
 * und keines der Pakete überschreibt den eines anderen.
 */
$GLOBALS['TL_LANG']['tl_settings']['vtone_licence_legend'] ??= 'V-T.ONE Licence management';

/*
 * Die Feldbeschriftung ist der Produktname: Sie ist die Überschrift über der Karte, und die
 * Lizenzverwaltung reiht pro V-T.ONE-Paket eine solche Karte aneinander.
 */
$GLOBALS['TL_LANG']['tl_settings']['cns_panel'] = ['Centralized Notification Suite', ''];

$GLOBALS['TL_LANG']['tl_settings']['cnsTier'] = 'Paket';
$GLOBALS['TL_LANG']['tl_settings']['cnsHost'] = 'Lizenzierter Host';
$GLOBALS['TL_LANG']['tl_settings']['cnsHosts'] = 'Abgedeckte Hosts';
$GLOBALS['TL_LANG']['tl_settings']['cnsVersion'] = 'Version';
$GLOBALS['TL_LANG']['tl_settings']['cnsTerm'] = 'Laufzeit';
$GLOBALS['TL_LANG']['tl_settings']['cnsPerpetual'] = 'Unbefristet';
$GLOBALS['TL_LANG']['tl_settings']['cnsActive'] = 'Lizenz aktiv';
$GLOBALS['TL_LANG']['tl_settings']['cnsInactive'] = 'Keine aktive Lizenz';

$GLOBALS['TL_LANG']['tl_settings']['cnsKeyLabel'] = 'Lizenzschlüssel';
$GLOBALS['TL_LANG']['tl_settings']['cnsKeyPlaceholder'] = 'XXXXX-XXXXX-XXXXX-XXXXX';
$GLOBALS['TL_LANG']['tl_settings']['cnsKeyHelp'] = 'Den für diese Website ausgestellten Lizenzschlüssel eingeben. Der Schlüssel wird im signierten Lizenzdatensatz gespeichert, nicht in der Einstellungsdatei, und wird nicht wieder angezeigt.';

$GLOBALS['TL_LANG']['tl_settings']['cnsActivate'] = 'Lizenz prüfen und aktivieren';
$GLOBALS['TL_LANG']['tl_settings']['cnsRefresh'] = 'Lizenz aktualisieren';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemove'] = 'Lizenz entfernen';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoveConfirm'] = 'Die gespeicherte Lizenz entfernen? Es werden keine Benachrichtigungen mehr versendet, bis wieder eine Lizenz aktiviert ist.';

$GLOBALS['TL_LANG']['tl_settings']['cnsActivated'] = 'Die Lizenz wurde aktiviert.';
$GLOBALS['TL_LANG']['tl_settings']['cnsUpdated'] = 'Die Lizenz wurde aktualisiert.';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoved'] = 'Die Lizenz wurde entfernt.';
$GLOBALS['TL_LANG']['tl_settings']['cnsFailed'] = 'Die Lizenz konnte nicht verifiziert werden. Bitte den Schlüssel prüfen und erneut versuchen.';
$GLOBALS['TL_LANG']['tl_settings']['cnsKeyMissing'] = 'Bitte zuerst einen Lizenzschlüssel eingeben.';
$GLOBALS['TL_LANG']['tl_settings']['cnsNothingStored'] = 'Es ist keine Lizenz gespeichert, die aktualisiert werden könnte. Bitte einen Lizenzschlüssel eingeben und aktivieren.';

$GLOBALS['TL_LANG']['tl_settings']['cnsReasonNone'] = 'Es wurde noch keine Lizenz aktiviert.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonHost'] = 'Für keine Startseite ist eine Domain konfiguriert, oder die Lizenz deckt keine konfigurierte Domain ab.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonExpired'] = 'Die gespeicherte Lizenz ist abgelaufen.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonFuture'] = 'Die gespeicherte Lizenz ist noch nicht gültig.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonGeneric'] = 'Die gespeicherte Lizenz konnte nicht verifiziert werden. Bitte erneut aktivieren.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonRevoked'] = 'Die Lizenz für diese Website wurde zurückgezogen. Bitte kontaktieren Sie uns, falls dies ein Irrtum ist.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonSuperseded'] = 'Die gespeicherte Lizenz ist älter als die zuletzt empfangene und wurde deshalb ignoriert. Bitte die aktuelle Lizenz erneut aktivieren.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonStale'] = 'Die Lizenz wurde nicht innerhalb des vorgesehenen Zeitraums erneut geprüft. Bitte „Lizenz aktualisieren“ verwenden, sobald diese Website den Lizenzdienst wieder erreichen kann.';

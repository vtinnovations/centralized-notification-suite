<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification']['title_legend'] = 'Titel und Alias';

$GLOBALS['TL_LANG']['tl_notification']['title'] = ['Titel', 'Geben Sie einen Namen für diese Benachrichtigung ein.'];
$GLOBALS['TL_LANG']['tl_notification']['alias'] = ['Alias', 'Ein eindeutiger Alias. Diese Zeichenkette übergibt Ihr Code an CentralizedNotificationSuite::send(). Leer lassen, um ihn aus dem Titel zu erzeugen.'];
$GLOBALS['TL_LANG']['tl_notification']['type'] = ['Ausgelöst durch', 'Was diese Benachrichtigung versendet. Der Typ bestimmt, welche Tokens Ihnen beim Bearbeiten der Nachricht angeboten werden und welche Benachrichtigungen ein Auslöser zur Auswahl stellt.'];

$GLOBALS['TL_LANG']['tl_notification']['type_options'] = [
    'form' => 'Ein Formularversand',
    'member' => 'Eine Mitglieder-Aktion (Registrierung, Passwort zurücksetzen, …)',
    'comment' => 'Ein neuer Kommentar',
    'newsletter' => 'Eine Newsletter-Anmeldung',
    'custom' => 'Ihr eigener Code',
];

$GLOBALS['TL_LANG']['tl_notification']['new'] = ['Neue Benachrichtigung', 'Eine neue Benachrichtigung erstellen'];
$GLOBALS['TL_LANG']['tl_notification']['edit'] = ['Nachrichten bearbeiten', 'Die Nachrichten der Benachrichtigung ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_notification']['editheader'] = ['Einstellungen bearbeiten', 'Die Einstellungen der Benachrichtigung ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_notification']['copy'] = ['Benachrichtigung duplizieren', 'Benachrichtigung ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_notification']['delete'] = ['Benachrichtigung löschen', 'Benachrichtigung ID %s löschen'];
$GLOBALS['TL_LANG']['tl_notification']['show'] = ['Details', 'Die Details der Benachrichtigung ID %s anzeigen'];

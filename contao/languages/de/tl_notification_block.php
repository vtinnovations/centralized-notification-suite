<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_block']['type_legend'] = 'Baustein';
$GLOBALS['TL_LANG']['tl_notification_block']['content_legend'] = 'Inhalt';
$GLOBALS['TL_LANG']['tl_notification_block']['layout_legend'] = 'Abstände';
$GLOBALS['TL_LANG']['tl_notification_block']['publish_legend'] = 'Sichtbarkeit';

$GLOBALS['TL_LANG']['tl_notification_block']['type'] = ['Bausteintyp', 'Was dieser Baustein ist. Bei einer Änderung erscheinen die Einstellungen dieses Typs.'];
$GLOBALS['TL_LANG']['tl_notification_block']['heading'] = ['Überschrift', 'Der Text der Überschrift. Tokens wie ##name## sind erlaubt.'];
$GLOBALS['TL_LANG']['tl_notification_block']['body_text'] = ['Text', 'Eine Leerzeile trennt Absätze, ein einfacher Umbruch erzeugt einen Zeilenumbruch. Tokens wie ##name## sind erlaubt.'];
$GLOBALS['TL_LANG']['tl_notification_block']['link_text'] = ['Linktext', 'Die Beschriftung des Buttons oder Links.'];
$GLOBALS['TL_LANG']['tl_notification_block']['link_url'] = ['Linkziel', 'Eine vollständige URL, ein Pfad ab /, eine mailto:-Adresse, ein Insert-Tag oder ein Token.'];
$GLOBALS['TL_LANG']['tl_notification_block']['image'] = ['Bild', 'Ein Bild auswählen. JPG, PNG oder GIF – SVG und WebP werden von E-Mail-Programmen nicht zuverlässig dargestellt.'];
$GLOBALS['TL_LANG']['tl_notification_block']['image_alt'] = ['Alternativtext', 'Wird angezeigt, wenn Bilder blockiert sind – was die meisten Empfänger zuerst sehen.'];
$GLOBALS['TL_LANG']['tl_notification_block']['image_width'] = ['Bildbreite (px)', 'Maximal 536, die nutzbare Breite im Layout. Größere Werte werden reduziert.'];
$GLOBALS['TL_LANG']['tl_notification_block']['align'] = ['Ausrichtung', 'Wie dieser Baustein in der Nachricht ausgerichtet wird.'];
$GLOBALS['TL_LANG']['tl_notification_block']['space_after'] = ['Abstand unten (px)', 'Abstand zwischen diesem und dem nächsten Baustein.'];
$GLOBALS['TL_LANG']['tl_notification_block']['published'] = ['Sichtbar', 'Diesen Baustein in die Nachricht aufnehmen.'];

$GLOBALS['TL_LANG']['tl_notification_block']['type_options'] = [
    'heading' => 'Überschrift',
    'paragraph' => 'Text',
    'list' => 'Liste',
    'details' => 'Detailtabelle',
    'token' => 'Generierter Inhalt',
    'button' => 'Button',
    'image' => 'Bild',
    'teaser' => 'Bild mit Text',
    'divider' => 'Trennlinie',
    'html' => 'Eigenes HTML',
];

$GLOBALS['TL_LANG']['tl_notification_block']['heading_level'] = ['Größe', 'Wie prominent die Überschrift erscheint.'];
$GLOBALS['TL_LANG']['tl_notification_block']['heading_level_options'] = [
    'h1' => 'Groß',
    'h2' => 'Mittel',
    'h3' => 'Klein',
];

$GLOBALS['TL_LANG']['tl_notification_block']['text_style'] = ['Stil', 'Normaler Text, ein größerer Einleitungsabsatz oder kleines Kleingedrucktes.'];
$GLOBALS['TL_LANG']['tl_notification_block']['text_style_options'] = [
    'normal' => 'Normal',
    'lead' => 'Einleitung',
    'muted' => 'Kleingedrucktes',
];

$GLOBALS['TL_LANG']['tl_notification_block']['list_style'] = ['Listentyp', 'Mit Punkten oder mit Nummern.'];
$GLOBALS['TL_LANG']['tl_notification_block']['list_style_options'] = [
    'bullet' => 'Aufzählung',
    'number' => 'Nummeriert',
];

$GLOBALS['TL_LANG']['tl_notification_block']['button_style'] = ['Button-Stil', 'Mit der Markenfarbe gefüllt oder nur umrandet.'];
$GLOBALS['TL_LANG']['tl_notification_block']['button_style_options'] = [
    'solid' => 'Gefüllt',
    'outline' => 'Umrandet',
];

$GLOBALS['TL_LANG']['tl_notification_block']['teaser_layout'] = ['Anordnung', 'Wo das Bild steht. Auf schmalen Bildschirmen werden die Spalten automatisch untereinander gesetzt.'];
$GLOBALS['TL_LANG']['tl_notification_block']['teaser_layout_options'] = [
    'image_left' => 'Bild links',
    'image_right' => 'Bild rechts',
    'image_top' => 'Bild oben',
];

$GLOBALS['TL_LANG']['tl_notification_block']['details_rows'] = ['Zeilen', 'Je Zeile eine Bezeichnung und ein Wert. Tokens wie ##email## sind in beiden erlaubt.'];
$GLOBALS['TL_LANG']['tl_notification_block']['token_name'] = ['Inhalt', 'Welcher generierte Inhalt eingefügt wird, z. B. die Liste aller übermittelten Formularfelder.'];
$GLOBALS['TL_LANG']['tl_notification_block']['custom_html'] = ['HTML', 'Markup wird unverändert eingefügt. Für E-Mails tabellenbasiertes Markup verwenden. Ein <style>-Block würde die ganze Nachricht betreffen und wird entfernt.'];

$GLOBALS['TL_LANG']['tl_notification_block']['align_options'] = [
    'left' => 'Links',
    'center' => 'Zentriert',
    'right' => 'Rechts',
];

$GLOBALS['TL_LANG']['tl_notification_block']['new'] = ['Neuer Baustein', 'Einen Baustein hinzufügen'];
$GLOBALS['TL_LANG']['tl_notification_block']['edit'] = ['Baustein bearbeiten', 'Baustein ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_notification_block']['copy'] = ['Baustein duplizieren', 'Baustein ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_notification_block']['cut'] = ['Baustein verschieben', 'Baustein ID %s verschieben'];
$GLOBALS['TL_LANG']['tl_notification_block']['delete'] = ['Baustein löschen', 'Baustein ID %s löschen'];
$GLOBALS['TL_LANG']['tl_notification_block']['show'] = ['Details', 'Details des Bausteins ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_notification_block']['toggle'] = ['Baustein ein-/ausblenden', 'Baustein ID %s ein- oder ausblenden'];

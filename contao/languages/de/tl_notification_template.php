<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_template']['title_legend'] = 'Titel';
$GLOBALS['TL_LANG']['tl_notification_template']['layout_legend'] = 'Layout';
$GLOBALS['TL_LANG']['tl_notification_template']['style_legend'] = 'Styles';
$GLOBALS['TL_LANG']['tl_notification_template']['publish_legend'] = 'Veröffentlichung';

$GLOBALS['TL_LANG']['tl_notification_template']['title'] = ['Titel', 'Ein Name für dieses Layout, z. B. „Standard-Layout“.'];
$GLOBALS['TL_LANG']['tl_notification_template']['preheader'] = ['Vorschautext', 'Die versteckte Zeile, die die meisten E-Mail-Programme neben dem Betreff im Postfach anzeigen. Ohne sie zeigen sie die ersten Worte Ihres Markups. Tokens sind erlaubt.'];
$GLOBALS['TL_LANG']['tl_notification_template']['layout_mode'] = ['Layout-Art', 'Wählen Sie, ob Sie ein vollständiges HTML-Dokument einfügen oder Kopf und Fuß um den Nachrichteninhalt legen.'];
$GLOBALS['TL_LANG']['tl_notification_template']['wrapper_html'] = ['HTML-Dokument', 'Fügen Sie hier Ihr komplettes E-Mail-HTML ein und setzen Sie ##message_body## an die Stelle, an die der Nachrichteninhalt gehört. Ihr Markup wird genau so gespeichert, wie Sie es einfügen.'];
$GLOBALS['TL_LANG']['tl_notification_template']['header_html'] = ['Kopfbereich', 'Markup vor dem Nachrichteninhalt, z. B. ein Logo und ein farbiger Balken.'];
$GLOBALS['TL_LANG']['tl_notification_template']['footer_html'] = ['Fußbereich', 'Markup nach dem Nachrichteninhalt, z. B. Anschrift und Rechtshinweis.'];
$GLOBALS['TL_LANG']['tl_notification_template']['css'] = ['CSS', 'Styles für dieses Layout, ohne &lt;style&gt;-Tags. Sie werden vor dem Versand in style-Attribute geschrieben – genau das lässt sie in Outlook und Gmail funktionieren. @media-Regeln lassen sich nicht inline schreiben und bleiben als Style-Block erhalten.'];
$GLOBALS['TL_LANG']['tl_notification_template']['inline_css'] = ['CSS inline schreiben', 'Jede Regel vor dem Versand auf die passenden Elemente übertragen. Lassen Sie das aktiviert, außer Sie wissen, dass das Programm der Empfänger Style-Blöcke unterstützt.'];
$GLOBALS['TL_LANG']['tl_notification_template']['published'] = ['Veröffentlicht', 'Dieses Layout in Nachrichten auswählbar machen.'];

$GLOBALS['TL_LANG']['tl_notification_template']['layout_mode_options'] = [
    'design' => 'Ein fertiges Design',
    'header_footer' => 'Kopf und Fuß um den Inhalt',
    'wrapper' => 'Vollständiges HTML-Dokument mit ##message_body##',
];

$GLOBALS['TL_LANG']['tl_notification_template']['new'] = ['Neues Layout', 'Ein neues E-Mail-Layout erstellen'];
$GLOBALS['TL_LANG']['tl_notification_template']['edit'] = ['Layout bearbeiten', 'Layout ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_notification_template']['copy'] = ['Layout duplizieren', 'Layout ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_notification_template']['delete'] = ['Layout löschen', 'Layout ID %s löschen'];
$GLOBALS['TL_LANG']['tl_notification_template']['show'] = ['Details', 'Die Details des Layouts ID %s anzeigen'];

$GLOBALS['TL_LANG']['tl_notification_template']['design'] = ['Design', 'Ein fertiges Design wählen. Farben und Logo kommen aus dem Branding, jedes Design ist damit sofort im Markenbild.'];

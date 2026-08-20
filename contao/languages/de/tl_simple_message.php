<?php

$GLOBALS['TL_LANG']['tl_simple_message']['gateway_legend'] = 'Absender und Sprache';
$GLOBALS['TL_LANG']['tl_simple_message']['content_legend'] = 'Nur-Text';
$GLOBALS['TL_LANG']['tl_simple_message']['html_legend'] = 'HTML';
$GLOBALS['TL_LANG']['tl_simple_message']['recipients_legend'] = 'Empfänger';
$GLOBALS['TL_LANG']['tl_simple_message']['attachment_legend'] = 'Anhänge';
$GLOBALS['TL_LANG']['tl_simple_message']['publish_legend'] = 'Veröffentlichung';

$GLOBALS['TL_LANG']['tl_simple_message']['gateway'] = ['Absender', 'Wählen Sie den Absender, der diese Nachricht versendet.'];
$GLOBALS['TL_LANG']['tl_simple_message']['language'] = ['Sprache', 'Die Sprache, in der diese Nachricht verfasst ist.'];
$GLOBALS['TL_LANG']['tl_simple_message']['fallback'] = ['Fallback-Nachricht', 'Diese Nachricht verwenden, wenn keine Nachricht zur angeforderten Sprache passt.'];
$GLOBALS['TL_LANG']['tl_simple_message']['subject'] = ['Betreff', 'Der Betreff der Nachricht. Sie können einfache Tokens verwenden, z. B. ##token##.'];
$GLOBALS['TL_LANG']['tl_simple_message']['text'] = ['Nur-Text', 'Der Nur-Text-Inhalt. Sie können einfache Tokens verwenden, z. B. ##token##. Leer lassen, um ihn aus dem HTML zu erzeugen.'];
$GLOBALS['TL_LANG']['tl_simple_message']['html'] = ['HTML', 'Fügen Sie Ihr HTML hier ein oder schreiben Sie es. Token-Werte werden maskiert und ihre Zeilenumbrüche in &lt;br&gt; umgewandelt, damit übermittelte Inhalte Ihr Markup nicht zerstören können; Tokens, deren Name auf „_html“ endet (z. B. ##all_fields_html##), werden als Markup eingefügt.'];
$GLOBALS['TL_LANG']['tl_simple_message']['auto_plaintext'] = ['Nur-Text aus dem HTML erzeugen', 'Den Text-Teil aus dem HTML ableiten, statt beide von Hand zu pflegen. Links werden als „Bezeichnung (URL)“ übernommen. Geschieht automatisch, wenn das Nur-Text-Feld leer ist.'];
$GLOBALS['TL_LANG']['tl_simple_message']['template'] = ['Layout', 'Diesen Inhalt optional in ein wiederverwendbares Layout einbetten, damit Kopf, Fuß und CSS mit Ihren übrigen Nachrichten geteilt werden. Leer lassen, um das HTML unverändert zu senden.'];
$GLOBALS['TL_LANG']['tl_simple_message']['embed_images'] = ['Bilder einbetten', 'Bilder dieser Website anhängen statt zu verlinken, damit sie ohne Freigabe externer Inhalte angezeigt werden. Vergrößert die Nachricht.'];
$GLOBALS['TL_LANG']['tl_simple_message']['recipients'] = ['Empfänger', 'Eine oder mehrere E-Mail-Adressen, durch Komma oder Semikolon getrennt. Tokens sind erlaubt, z. B. ##token##. Ungültige Adressen werden übersprungen und protokolliert, statt die ganze Nachricht scheitern zu lassen.'];
$GLOBALS['TL_LANG']['tl_simple_message']['cc'] = ['CC', 'Diese Adressen optional in Kopie setzen. Alle Empfänger sehen sie.'];
$GLOBALS['TL_LANG']['tl_simple_message']['bcc'] = ['BCC', 'Diese Adressen optional in Blindkopie setzen, z. B. um jede Benachrichtigung zu archivieren.'];
$GLOBALS['TL_LANG']['tl_simple_message']['reply_to'] = ['Antwort an', 'Die Antwortadresse optional überschreiben, z. B. ##email##, um direkt der Person zu antworten, die das Formular abgeschickt hat. Fällt auf die Einstellung des Absenders zurück.'];
$GLOBALS['TL_LANG']['tl_simple_message']['priority'] = ['Priorität', 'Die Prioritätsmarkierung. Die meisten E-Mail-Programme ignorieren sie; manche Spamfilter bewerten „Höchste“ negativ.'];
$GLOBALS['TL_LANG']['tl_simple_message']['attachments'] = ['Anhänge', 'Eine oder mehrere Dateien aus der Dateiverwaltung an diese Nachricht anhängen.'];
$GLOBALS['TL_LANG']['tl_simple_message']['published'] = ['Veröffentlicht', 'Die Nachricht aktivieren.'];
$GLOBALS['TL_LANG']['tl_simple_message']['start'] = ['Anzeigen ab', 'Die Nachricht wird vor diesem Datum nicht versendet.'];
$GLOBALS['TL_LANG']['tl_simple_message']['stop'] = ['Anzeigen bis', 'Die Nachricht wird nach diesem Datum nicht mehr versendet.'];

$GLOBALS['TL_LANG']['tl_simple_message']['tokenHelpHeader'] = ['Token', 'Enthält'];

$GLOBALS['TL_LANG']['tl_simple_message']['priority_options'] = [
    1 => 'Höchste',
    2 => 'Hoch',
    3 => 'Normal',
    4 => 'Niedrig',
    5 => 'Niedrigste',
];

$GLOBALS['TL_LANG']['tl_simple_message']['new'] = ['Neue Nachricht', 'Eine neue Nachricht erstellen'];
$GLOBALS['TL_LANG']['tl_simple_message']['edit'] = ['Nachricht bearbeiten', 'Nachricht ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_simple_message']['copy'] = ['Nachricht duplizieren', 'Nachricht ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_simple_message']['delete'] = ['Nachricht löschen', 'Nachricht ID %s löschen'];
$GLOBALS['TL_LANG']['tl_simple_message']['show'] = ['Details', 'Die Details der Nachricht ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_simple_message']['preview'] = ['Vorschau', 'Ansehen, wie Nachricht ID %s aussieht, mit Beispielwerten'];
$GLOBALS['TL_LANG']['tl_simple_message']['testsend'] = ['Test senden', 'Nachricht ID %s an eine Adresse Ihrer Wahl senden'];

$GLOBALS['TL_LANG']['tl_simple_message']['gatewayMissing'] = 'Der zugewiesene Absender existiert nicht mehr, diese Nachricht wird nicht versendet.';
$GLOBALS['TL_LANG']['tl_simple_message']['gatewayUnpublished'] = 'Der Absender „%s“ ist nicht veröffentlicht, diese Nachricht wird nicht versendet.';
$GLOBALS['TL_LANG']['tl_simple_message']['gatewayTypeMissing'] = 'Für den Typ „%s“ ist kein Gateway installiert, diese Nachricht wird nicht versendet.';

$GLOBALS['TL_LANG']['tl_simple_message']['previewSubject'] = 'Betreff';
$GLOBALS['TL_LANG']['tl_simple_message']['previewDesktop'] = 'Desktop';
$GLOBALS['TL_LANG']['tl_simple_message']['previewMobile'] = 'Mobil';
$GLOBALS['TL_LANG']['tl_simple_message']['previewHtml'] = 'HTML';
$GLOBALS['TL_LANG']['tl_simple_message']['previewText'] = 'Nur-Text';
$GLOBALS['TL_LANG']['tl_simple_message']['previewNoHtml'] = 'Diese Nachricht hat keinen HTML-Teil.';
$GLOBALS['TL_LANG']['tl_simple_message']['previewSample'] = 'Die Token-Werte sind Beispiele, keine echten Daten.';

$GLOBALS['TL_LANG']['tl_simple_message']['testSendHeading'] = 'Testnachricht senden';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendIntro'] = 'Füllen Sie die Tokens dieser Nachricht aus und senden Sie sie an sich selbst. Empfänger, CC und BCC werden durch die Adresse unten ersetzt, niemand sonst erhält sie.';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendRecipient'] = 'Senden an';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendSubmit'] = 'Test senden';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendNoTokens'] = 'Diese Nachricht verwendet keine Tokens, die Sie ausfüllen müssen.';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendOk'] = 'Testnachricht an %s gesendet. Das Ergebnis steht im Versandprotokoll.';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendInvalidRecipient'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendNoGateway'] = 'Diese Nachricht hat keinen veröffentlichten Absender und kann nicht gesendet werden.';
$GLOBALS['TL_LANG']['tl_simple_message']['testSendFailed'] = 'Der Versand ist fehlgeschlagen. Die Ursache steht im Versandprotokoll.';

<?php

$GLOBALS['TL_LANG']['tl_simple_gateway']['title_legend'] = 'Titel und Typ';
$GLOBALS['TL_LANG']['tl_simple_gateway']['email_legend'] = 'E-Mail-Einstellungen';
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_legend'] = 'Webhook-Einstellungen';
$GLOBALS['TL_LANG']['tl_simple_gateway']['file_legend'] = 'Datei-Einstellungen';
$GLOBALS['TL_LANG']['tl_simple_gateway']['publish_legend'] = 'Veröffentlichung';

$GLOBALS['TL_LANG']['tl_simple_gateway']['title'] = ['Titel', 'Geben Sie einen Namen für diesen Absender ein.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['type'] = ['Typ', 'Der Typ bestimmt, wie die zugewiesenen Nachrichten versendet werden.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['sender_name'] = ['Absendername', 'Der Name, der als Absender erscheint.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['sender_email'] = ['Absenderadresse', 'Die E-Mail-Adresse, von der gesendet wird.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['reply_to'] = ['Standard-Antwortadresse', 'Optional die Antwortadresse für alle Nachrichten dieses Absenders. Eine Nachricht kann sie überschreiben.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['mailer_transport'] = ['Mailer-Transport', 'Optional einen benannten Symfony-Mailer-Transport wählen (siehe mailer.yaml). Leer lassen für den Standard-Transport.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['published'] = ['Veröffentlicht', 'Den Absender für Nachrichten verfügbar machen.'];

$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_url'] = ['Endpunkt-URL', 'Die URL, an die gesendet wird, z. B. ein Slack- oder Teams-Webhook oder ein Zapier-/n8n-/Make-Trigger.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_method'] = ['HTTP-Methode', 'Die meisten Webhook-Endpunkte erwarten POST.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_timeout'] = ['Timeout (Sekunden)', 'Wie lange auf den Endpunkt gewartet wird. Halten Sie den Wert klein: ein langsamer Endpunkt hält die auslösende Besucheraktion auf.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_headers'] = ['Zusätzliche Header', 'Weitere Request-Header, z. B. ein Authorization-Header. Content-Type wird automatisch auf application/json gesetzt.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_payload'] = ['JSON-Payload', 'Der Request-Body. Verwenden Sie ##subject##, ##text##, ##html##, ##recipients## sowie jedes Token der Benachrichtigung; Werte werden maskiert, sodass immer gültiges JSON entsteht. Leer lassen für {"text": "##subject##\n\n##text##"}, das Slack, Mattermost und Discord akzeptieren.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['file_dir'] = ['Verzeichnis', 'Wohin die Nachrichten geschrieben werden. Relative Pfade werden im Projekt aufgelöst. Leer lassen für var/simple-notify-mail.'];

$GLOBALS['TL_LANG']['tl_simple_gateway']['type_options'] = [
    'email' => 'E-Mail',
    'webhook' => 'Webhook / JSON (Slack, Teams, Zapier, …)',
    'file' => 'Datei (schreibt auf die Festplatte statt zu senden)',
];

$GLOBALS['TL_LANG']['tl_simple_gateway']['new'] = ['Neuer Absender', 'Einen neuen Absender erstellen'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['edit'] = ['Absender bearbeiten', 'Absender ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['copy'] = ['Absender duplizieren', 'Absender ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['delete'] = ['Absender löschen', 'Absender ID %s löschen'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['show'] = ['Details', 'Die Details des Absenders ID %s anzeigen'];

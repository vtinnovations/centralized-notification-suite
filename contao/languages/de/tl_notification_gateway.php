<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_gateway']['title_legend'] = 'Titel und Typ';
$GLOBALS['TL_LANG']['tl_notification_gateway']['email_legend'] = 'E-Mail-Einstellungen';
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_legend'] = 'Webhook-Einstellungen';
$GLOBALS['TL_LANG']['tl_notification_gateway']['file_legend'] = 'Datei-Einstellungen';
$GLOBALS['TL_LANG']['tl_notification_gateway']['publish_legend'] = 'Veröffentlichung';

$GLOBALS['TL_LANG']['tl_notification_gateway']['title'] = ['Titel', 'Geben Sie einen Namen für diesen Absender ein.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['type'] = ['Typ', 'Der Typ bestimmt, wie die zugewiesenen Nachrichten versendet werden.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['sender_name'] = ['Absendername', 'Der Name, der als Absender erscheint.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['sender_email'] = ['Absenderadresse', 'Die E-Mail-Adresse, von der gesendet wird.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['reply_to'] = ['Standard-Antwortadresse', 'Optional die Antwortadresse für alle Nachrichten dieses Absenders. Eine Nachricht kann sie überschreiben.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['mailer_transport'] = ['Mailer-Transport', 'Optional einen benannten Symfony-Mailer-Transport wählen (siehe mailer.yaml). Leer lassen für den Standard-Transport.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['published'] = ['Veröffentlicht', 'Den Absender für Nachrichten verfügbar machen.'];

$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_url'] = ['Endpunkt-URL', 'Die URL, an die gesendet wird: ein Slack-Incoming-Webhook, ein Microsoft-Teams-Workflows-Webhook, ein Google-Chat-Webhook oder ein Zapier-/n8n-/Make-Trigger. Die Payload-Hilfe unten enthält für jeden einen fertigen Body.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_method'] = ['HTTP-Methode', 'Die meisten Webhook-Endpunkte erwarten POST.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_timeout'] = ['Timeout (Sekunden)', 'Wie lange auf den Endpunkt gewartet wird. Halten Sie den Wert klein: ein langsamer Endpunkt hält die auslösende Besucheraktion auf.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_headers'] = ['Zusätzliche Header', 'Weitere Request-Header, z. B. ein Authorization-Header. Content-Type wird automatisch auf application/json gesetzt.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_payload'] = ['JSON-Payload', 'Der Request-Body. Verwenden Sie ##subject##, ##text##, ##html##, ##recipients## sowie jedes Token der Benachrichtigung; Werte werden maskiert, sodass immer gültiges JSON entsteht. Leer lassen für {"text": "##subject##\n\n##text##"}, das Slack, Mattermost und Discord akzeptieren.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['file_dir'] = ['Verzeichnis', 'Wohin die Nachrichten geschrieben werden. Relative Pfade werden im Projekt aufgelöst. Leer lassen für var/notification-mail.'];

$GLOBALS['TL_LANG']['tl_notification_gateway']['type_options'] = [
    'email' => 'E-Mail',
    'webhook' => 'Webhook / JSON (Slack, Teams, Zapier, …)',
    'file' => 'Datei (schreibt auf die Festplatte statt zu senden)',
];

$GLOBALS['TL_LANG']['tl_notification_gateway']['new'] = ['Neuer Absender', 'Einen neuen Absender erstellen'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['edit'] = ['Absender bearbeiten', 'Absender ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['copy'] = ['Absender duplizieren', 'Absender ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['delete'] = ['Absender löschen', 'Absender ID %s löschen'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['show'] = ['Details', 'Die Details des Absenders ID %s anzeigen'];

/*
 * Zeilen für den Hilfe-Assistenten des JSON-Payloads. GatewayDcaListener maskiert sie und
 * schreibt sie in $GLOBALS['TL_LANG']['XPL']; eine Zeile mit "headspan" wird zur Überschrift.
 */
$GLOBALS['TL_LANG']['tl_notification_gateway']['payloadHelp'] = [
    ['headspan', 'Wozu dieses Feld dient'],
    ['Zweck', 'Der Anfrage-Body, gesendet als application/json. Jedes ##token## wird ersetzt und JSON-maskiert, sodass ein Anführungszeichen oder Zeilenumbruch aus einem Formularfeld das JSON nie zerstören kann.'],
    ['Leer lassen', 'Sendet {"text": "##subject##\n\n##text##"} — das einfache Format, das Slack, Mattermost und Discord akzeptieren. Damit anfangen; ein eigener Payload ist nur für Formatierung nötig.'],

    ['headspan', 'Slack — formatierte Nachricht (Block Kit)'],
    ['Beispiel', '{
  "text": "##subject##",
  "blocks": [
    { "type": "header", "text": { "type": "plain_text", "text": "##subject##" } },
    { "type": "section", "fields": [
      { "type": "mrkdwn", "text": "*Name*\n##name##" },
      { "type": "mrkdwn", "text": "*E-Mail*\n##email##" }
    ]},
    { "type": "section", "text": { "type": "mrkdwn", "text": "*Nachricht*\n##message##" } },
    { "type": "divider" },
    { "type": "context", "elements": [ { "type": "mrkdwn", "text": "##host## · ##datim##" } ] }
  ]
}'],
    ['Hinweise', 'Das oberste "text" beibehalten: Slack nutzt es für die Benachrichtigungsvorschau und für Clients, die keine Blocks darstellen. Ein header wird nach 150 Zeichen gekürzt, eine section nach 3000. Slack-Auszeichnung ist *fett*, _kursiv_, `Code` und <url|Text> — kein HTML.'],

    ['headspan', 'Microsoft Teams — Workflows-Webhook (Adaptive Card)'],
    ['Beispiel', '{
  "type": "message",
  "attachments": [{
    "contentType": "application/vnd.microsoft.card.adaptive",
    "content": {
      "type": "AdaptiveCard",
      "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
      "version": "1.4",
      "body": [
        { "type": "TextBlock", "text": "##subject##", "weight": "Bolder", "size": "Medium", "wrap": true },
        { "type": "TextBlock", "text": "##text##", "wrap": true }
      ]
    }
  }]
}'],
    ['Hinweise', 'Die URL in Teams über das Kanalmenü holen: Weitere Optionen, dann Workflows, dann die Vorlage „In einem Kanal veröffentlichen, wenn eine Webhook-Anfrage empfangen wird“. Dafür ist ein Geschäfts- oder Schulkonto nötig; die kostenlosen Teams-Communities haben überhaupt keinen Webhook-Endpunkt. Eine alte Connector-URL auf webhook.office.com nutzt stattdessen die einfachere Form {"title": "##subject##", "text": "##text##"}.'],

    ['headspan', 'Google Chat'],
    ['Beispiel', '{ "text": "*##subject##*\n\n##text##" }'],
    ['Hinweise', 'Menü des Space, dann Apps und Integrationen, dann Webhooks. Erfordert Google Workspace.'],

    ['headspan', 'Verfügbare Tokens'],
    ['##subject##', 'Der gerenderte Betreff.'],
    ['##text##', 'Der Text-Inhalt. Für Chat der richtige Token: er wird aus dem HTML erzeugt, wenn „Text automatisch erzeugen“ aktiv ist.'],
    ['##html##', 'Der vollständige HTML-Inhalt. Chat-Dienste stellen kein HTML dar, daher nur für Endpunkte sinnvoll, die E-Mails speichern oder weiterleiten.'],
    ['##recipients##', 'Die gerenderte Empfängerliste. Ein Webhook liefert nicht an sie aus — das Ziel ist die Endpunkt-URL — sie ist also nur als Wert im Payload nützlich.'],
    ['##alias##', 'Der Alias der Benachrichtigung, aus der diese Nachricht stammt.'],
    ['##reference##', 'Eine eindeutige ID dieses Versands, nützlich zum Abgleich mit dem Versandprotokoll.'],
    ['##name##', 'Jeder Token der Benachrichtigung selbst: ein Formularfeldname, ein Mitgliederfeld oder ein universeller Token wie ##host##, ##datim##, ##page_url## oder ##admin_email##. Betreff- und Inhaltsfelder listen alle in ihrem eigenen Hilfe-Assistenten.'],
];

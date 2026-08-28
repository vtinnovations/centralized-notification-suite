<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_gateway']['title_legend'] = 'Title and type';
$GLOBALS['TL_LANG']['tl_notification_gateway']['email_legend'] = 'Email settings';
$GLOBALS['TL_LANG']['tl_notification_gateway']['publish_legend'] = 'Publish settings';

$GLOBALS['TL_LANG']['tl_notification_gateway']['title'] = ['Title', 'Enter a name for this gateway.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['type'] = ['Type', 'Select the gateway type that will send messages assigned to it.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['sender_name'] = ['Sender name', 'Enter the sender name.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['sender_email'] = ['Sender email address', 'Enter the sender email address.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['reply_to'] = ['Default reply address', 'Optionally set the reply address for every message using this gateway. A message can override it.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['mailer_transport'] = ['Mailer transport', 'Optionally select a named Symfony Mailer transport (see mailer.yaml). Leave blank to use the default transport.'];

$GLOBALS['TL_LANG']['tl_notification_gateway']['type_options'] = [
    'email' => 'Email',
    'webhook' => 'Webhook / JSON (Slack, Teams, Zapier, ...)',
    'file' => 'File (writes to disk instead of sending)',
];
$GLOBALS['TL_LANG']['tl_notification_gateway']['published'] = ['Published', 'Make the gateway available for use in messages.'];

$GLOBALS['TL_LANG']['tl_notification_gateway']['new'] = ['New gateway', 'Create a new gateway'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['edit'] = ['Edit gateway', 'Edit gateway ID %s'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['copy'] = ['Copy gateway', 'Copy gateway ID %s'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['delete'] = ['Delete gateway', 'Delete gateway ID %s'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['show'] = ['Gateway details', 'Show the details of gateway ID %s'];

$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_legend'] = 'Webhook settings';
$GLOBALS['TL_LANG']['tl_notification_gateway']['file_legend'] = 'File settings';

$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_url'] = ['Endpoint URL', 'The URL to post to: a Slack incoming webhook, a Microsoft Teams Workflows webhook, a Google Chat webhook, or a Zapier/n8n/Make trigger. The payload help below has a ready-made body for each.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_method'] = ['HTTP method', 'Most webhook endpoints expect POST.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_timeout'] = ['Timeout (seconds)', 'How long to wait for the endpoint. Keep it short: a slow endpoint holds up the visitor action that triggered the notification.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_headers'] = ['Extra headers', 'Additional request headers, e.g. an Authorization header. Content-Type is set to application/json automatically.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['webhook_payload'] = ['JSON payload', 'The request body. Use ##subject##, ##text##, ##html##, ##recipients## and any token of the notification; values are escaped so they are always valid JSON. Leave blank for {"text": "##subject##\n\n##text##"}, which Slack, Mattermost and Discord accept.'];
$GLOBALS['TL_LANG']['tl_notification_gateway']['file_dir'] = ['Directory', 'Where to write the messages. Relative paths are resolved inside the project. Leave blank for var/notification-mail.'];

/*
 * Rows for the JSON payload help wizard. GatewayDcaListener escapes these and pushes them
 * into $GLOBALS['TL_LANG']['XPL']; a row whose first value is "headspan" becomes a heading.
 */
$GLOBALS['TL_LANG']['tl_notification_gateway']['payloadHelp'] = [
    ['headspan', 'What this field is'],
    ['Purpose', 'The request body, sent as application/json. Every ##token## is replaced and JSON-escaped, so a quote or a line break in a form field can never break the JSON.'],
    ['Leave it empty', 'Sends {"text": "##subject##\n\n##text##"} — the simple format Slack, Mattermost and Discord all accept. Start here; only write your own payload when you want formatting.'],

    ['headspan', 'Slack — formatted message (Block Kit)'],
    ['Example', '{
  "text": "##subject##",
  "blocks": [
    { "type": "header", "text": { "type": "plain_text", "text": "##subject##" } },
    { "type": "section", "fields": [
      { "type": "mrkdwn", "text": "*Name*\n##name##" },
      { "type": "mrkdwn", "text": "*E-mail*\n##email##" }
    ]},
    { "type": "section", "text": { "type": "mrkdwn", "text": "*Message*\n##message##" } },
    { "type": "divider" },
    { "type": "context", "elements": [ { "type": "mrkdwn", "text": "##host## · ##datim##" } ] }
  ]
}'],
    ['Notes', 'Keep the top-level "text": Slack uses it for notification previews and for clients that cannot render blocks. A header truncates past 150 characters, a section past 3000. Slack markup is *bold*, _italic_, `code` and <url|label> — not HTML.'],

    ['headspan', 'Microsoft Teams — Workflows webhook (Adaptive Card)'],
    ['Example', '{
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
    ['Notes', 'Get the URL in Teams from the channel menu: More options, then Workflows, then the template "Post to a channel when a webhook request is received". This needs a work or school account; the free Teams Communities has no webhook endpoint at all. A legacy connector URL on webhook.office.com takes the simpler form {"title": "##subject##", "text": "##text##"} instead.'],

    ['headspan', 'Google Chat'],
    ['Example', '{ "text": "*##subject##*\n\n##text##" }'],
    ['Notes', 'Space menu, then Apps & integrations, then Webhooks. Needs Google Workspace.'],

    ['headspan', 'Tokens you can use here'],
    ['##subject##', 'The rendered subject line.'],
    ['##text##', 'The plain-text body. This is the one to use for chat: it is generated from the HTML when "Generate plain text" is on.'],
    ['##html##', 'The full HTML body. Chat services do not render HTML, so this is only useful for an endpoint that stores or forwards mail.'],
    ['##recipients##', 'The rendered recipient list. A webhook does not deliver to it — the destination is the endpoint URL — so it is only useful as a value inside the payload.'],
    ['##alias##', 'The alias of the notification that produced this message.'],
    ['##reference##', 'A unique id for this send, useful for correlating with the send log.'],
    ['##name##', 'Any token of the notification itself: a form field name, a member field, or a universal token such as ##host##, ##datim##, ##page_url## or ##admin_email##. The subject and body fields list them all in their own help wizard.'],
];

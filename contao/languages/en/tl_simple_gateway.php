<?php

$GLOBALS['TL_LANG']['tl_simple_gateway']['title_legend'] = 'Title and type';
$GLOBALS['TL_LANG']['tl_simple_gateway']['email_legend'] = 'Email settings';
$GLOBALS['TL_LANG']['tl_simple_gateway']['publish_legend'] = 'Publish settings';

$GLOBALS['TL_LANG']['tl_simple_gateway']['title'] = ['Title', 'Enter a name for this gateway.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['type'] = ['Type', 'Select the gateway type that will send messages assigned to it.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['sender_name'] = ['Sender name', 'Enter the sender name.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['sender_email'] = ['Sender email address', 'Enter the sender email address.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['reply_to'] = ['Default reply address', 'Optionally set the reply address for every message using this gateway. A message can override it.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['mailer_transport'] = ['Mailer transport', 'Optionally select a named Symfony Mailer transport (see mailer.yaml). Leave blank to use the default transport.'];

$GLOBALS['TL_LANG']['tl_simple_gateway']['type_options'] = [
    'email' => 'Email',
    'webhook' => 'Webhook / JSON (Slack, Teams, Zapier, ...)',
    'file' => 'File (writes to disk instead of sending)',
];
$GLOBALS['TL_LANG']['tl_simple_gateway']['published'] = ['Published', 'Make the gateway available for use in messages.'];

$GLOBALS['TL_LANG']['tl_simple_gateway']['new'] = ['New gateway', 'Create a new gateway'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['edit'] = ['Edit gateway', 'Edit gateway ID %s'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['copy'] = ['Copy gateway', 'Copy gateway ID %s'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['delete'] = ['Delete gateway', 'Delete gateway ID %s'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['show'] = ['Gateway details', 'Show the details of gateway ID %s'];

$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_legend'] = 'Webhook settings';
$GLOBALS['TL_LANG']['tl_simple_gateway']['file_legend'] = 'File settings';

$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_url'] = ['Endpoint URL', 'The URL to post to, e.g. a Slack or Teams incoming webhook, or a Zapier/n8n/Make trigger.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_method'] = ['HTTP method', 'Most webhook endpoints expect POST.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_timeout'] = ['Timeout (seconds)', 'How long to wait for the endpoint. Keep it short: a slow endpoint holds up the visitor action that triggered the notification.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_headers'] = ['Extra headers', 'Additional request headers, e.g. an Authorization header. Content-Type is set to application/json automatically.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['webhook_payload'] = ['JSON payload', 'The request body. Use ##subject##, ##text##, ##html##, ##recipients## and any token of the notification; values are escaped so they are always valid JSON. Leave blank for {"text": "##subject##\n\n##text##"}, which Slack, Mattermost and Discord accept.'];
$GLOBALS['TL_LANG']['tl_simple_gateway']['file_dir'] = ['Directory', 'Where to write the messages. Relative paths are resolved inside the project. Leave blank for var/simple-notify-mail.'];

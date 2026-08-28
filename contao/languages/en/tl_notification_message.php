<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_message']['gateway_legend'] = 'Gateway and language';
$GLOBALS['TL_LANG']['tl_notification_message']['content_legend'] = 'Plain text';
$GLOBALS['TL_LANG']['tl_notification_message']['html_legend'] = 'HTML';
$GLOBALS['TL_LANG']['tl_notification_message']['recipients_legend'] = 'Recipients';
$GLOBALS['TL_LANG']['tl_notification_message']['attachment_legend'] = 'Attachments';
$GLOBALS['TL_LANG']['tl_notification_message']['publish_legend'] = 'Publish settings';

$GLOBALS['TL_LANG']['tl_notification_message']['gateway'] = ['Gateway', 'Select the gateway that will send this message.'];
$GLOBALS['TL_LANG']['tl_notification_message']['language'] = ['Language', 'Select the language this message is written in.'];
$GLOBALS['TL_LANG']['tl_notification_message']['fallback'] = ['Fallback message', 'Use this message when no message matches the requested language.'];
$GLOBALS['TL_LANG']['tl_notification_message']['subject'] = ['Subject', 'Insert values with ##token##, e.g. ##name## for a form field whose "name" is name. Click the help icon for every token this notification provides.'];
$GLOBALS['TL_LANG']['tl_notification_message']['text'] = ['Plain text', 'Insert a single field with ##token##, using its "name" from the form generator (e.g. ##email##) — or list everything that was submitted at once with ##all_fields## (##all_fields_filled## skips empty fields). Click the help icon for the full list. Leave blank to generate this from the HTML body.'];
$GLOBALS['TL_LANG']['tl_notification_message']['html'] = ['HTML', 'Same tokens as the plain text part, e.g. ##email## for one field or ##all_fields_html## for every submitted field as a table. Click the help icon for the full list. Token values are escaped and their line breaks turned into &lt;br&gt;, so submitted content cannot break your markup; only tokens whose name ends in "_html" are inserted as markup.'];
$GLOBALS['TL_LANG']['tl_notification_message']['auto_plaintext'] = ['Generate the plain text from the HTML', 'Derive the text part from the HTML body instead of maintaining both by hand. Links are kept as "label (url)". Happens automatically when the plain text field is empty.'];
$GLOBALS['TL_LANG']['tl_notification_message']['body_mode'] = ['Body', 'Build the body from blocks you can drag to reorder, or write the HTML yourself.'];
$GLOBALS['TL_LANG']['tl_notification_message']['body_mode_options'] = [
    'blocks' => 'Blocks',
    'html' => 'Own HTML',
];
$GLOBALS['TL_LANG']['tl_notification_message']['template'] = ['Layout', 'Optionally wrap this body in a reusable layout, so the header, footer and CSS are shared with your other messages. Leave blank to send the HTML exactly as entered.'];
$GLOBALS['TL_LANG']['tl_notification_message']['embed_images'] = ['Embed images', 'Attach images that live on this site instead of linking to them, so they display without the recipient having to allow remote content. Makes the message larger.'];
$GLOBALS['TL_LANG']['tl_notification_message']['recipients'] = ['Recipients', 'Enter one or more email addresses, separated by comma or semicolon. You can use simple tokens, e.g. ##token##. Invalid addresses are skipped and logged rather than failing the whole message.'];
$GLOBALS['TL_LANG']['tl_notification_message']['cc'] = ['CC', 'Optionally copy these addresses. Every recipient can see them.'];
$GLOBALS['TL_LANG']['tl_notification_message']['bcc'] = ['BCC', 'Optionally blind-copy these addresses, e.g. to archive a copy of every notification.'];
$GLOBALS['TL_LANG']['tl_notification_message']['reply_to'] = ['Reply to', 'Optionally override the reply address, e.g. ##email## to reply directly to the person who submitted the form. Falls back to the gateway setting.'];
$GLOBALS['TL_LANG']['tl_notification_message']['priority'] = ['Priority', 'The message priority flag. Most mail clients ignore it; some spam filters penalise "highest".'];
$GLOBALS['TL_LANG']['tl_notification_message']['attachments'] = ['Attachments', 'Select one or more files from the file system to attach to this message.'];

$GLOBALS['TL_LANG']['tl_notification_message']['tokenHelpHeader'] = ['Token', 'Contains'];
$GLOBALS['TL_LANG']['tl_notification_message']['tokenHelpAny'] = 'Available to every notification';

$GLOBALS['TL_LANG']['tl_notification_message']['priority_options'] = [
    1 => 'Highest',
    2 => 'High',
    3 => 'Normal',
    4 => 'Low',
    5 => 'Lowest',
];
$GLOBALS['TL_LANG']['tl_notification_message']['published'] = ['Published', 'Make the message active.'];
$GLOBALS['TL_LANG']['tl_notification_message']['start'] = ['Start date', 'The message will not be sent before this date.'];
$GLOBALS['TL_LANG']['tl_notification_message']['stop'] = ['Stop date', 'The message will no longer be sent after this date.'];

$GLOBALS['TL_LANG']['tl_notification_message']['new'] = ['New message', 'Create a new message'];
$GLOBALS['TL_LANG']['tl_notification_message']['edit'] = ['Edit message', 'Edit message ID %s'];
$GLOBALS['TL_LANG']['tl_notification_message']['copy'] = ['Copy message', 'Copy message ID %s'];
$GLOBALS['TL_LANG']['tl_notification_message']['delete'] = ['Delete message', 'Delete message ID %s'];
$GLOBALS['TL_LANG']['tl_notification_message']['show'] = ['Message details', 'Show the details of message ID %s'];

$GLOBALS['TL_LANG']['tl_notification_message']['preview'] = ['Preview', 'See how message ID %s will look, with sample token values'];
$GLOBALS['TL_LANG']['tl_notification_message']['testsend'] = ['Send a test', 'Send message ID %s to an address of your choice'];

$GLOBALS['TL_LANG']['tl_notification_message']['gatewayMissing'] = 'The sender assigned to this message no longer exists, so it will not be sent.';
$GLOBALS['TL_LANG']['tl_notification_message']['gatewayUnpublished'] = 'The sender "%s" is not published, so this message will not be sent.';
$GLOBALS['TL_LANG']['tl_notification_message']['gatewayTypeMissing'] = 'No gateway is installed for type "%s", so this message will not be sent.';
$GLOBALS['TL_LANG']['tl_notification_message']['recipientsMissing'] = 'This message has no recipients, so it will not be sent.';

$GLOBALS['TL_LANG']['tl_notification_message']['previewSubject'] = 'Subject';
$GLOBALS['TL_LANG']['tl_notification_message']['previewDesktop'] = 'Desktop';
$GLOBALS['TL_LANG']['tl_notification_message']['previewMobile'] = 'Mobile';
$GLOBALS['TL_LANG']['tl_notification_message']['previewHtml'] = 'HTML';
$GLOBALS['TL_LANG']['tl_notification_message']['previewText'] = 'Plain text';
$GLOBALS['TL_LANG']['tl_notification_message']['previewNoHtml'] = 'This message has no HTML part.';
$GLOBALS['TL_LANG']['tl_notification_message']['previewSample'] = 'Token values are samples, not real data.';

$GLOBALS['TL_LANG']['tl_notification_message']['testSendHeading'] = 'Send a test message';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendIntro'] = 'Fill in the tokens this message uses and send it to yourself. Recipients, CC and BCC are replaced by the address below, so nobody else receives it.';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendRecipient'] = 'Send to';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendSubmit'] = 'Send test';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendNoTokens'] = 'This message uses no tokens you need to fill in.';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendOk'] = 'Test message sent to %s. Check the send log for the delivery result.';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendInvalidRecipient'] = 'Enter a valid email address.';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendNoGateway'] = 'This message has no published sender, so it cannot be sent.';
$GLOBALS['TL_LANG']['tl_notification_message']['testSendFailed'] = 'Sending failed. The send log has the reason.';

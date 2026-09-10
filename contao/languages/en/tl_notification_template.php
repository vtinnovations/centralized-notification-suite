<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_template']['title_legend'] = 'Title';
$GLOBALS['TL_LANG']['tl_notification_template']['layout_legend'] = 'Layout';
$GLOBALS['TL_LANG']['tl_notification_template']['style_legend'] = 'Styles';
$GLOBALS['TL_LANG']['tl_notification_template']['publish_legend'] = 'Publish settings';

$GLOBALS['TL_LANG']['tl_notification_template']['title'] = ['Title', 'Enter a name for this layout, e.g. "Brand default".'];
$GLOBALS['TL_LANG']['tl_notification_template']['preheader'] = ['Preview text', 'The hidden line most mail clients show next to the subject in the inbox. Without one they show the first words of your markup instead. Simple tokens are allowed.'];
$GLOBALS['TL_LANG']['tl_notification_template']['layout_mode'] = ['Layout type', 'Choose whether you paste a complete HTML document or supply a header and footer around the message body.'];
$GLOBALS['TL_LANG']['tl_notification_template']['wrapper_html'] = ['HTML document', 'Paste your complete e-mail HTML here and put ##message_body## where the message content belongs. Your markup is stored exactly as pasted.'];
$GLOBALS['TL_LANG']['tl_notification_template']['header_html'] = ['Header', 'Markup placed before the message body, e.g. a logo and a coloured bar.'];
$GLOBALS['TL_LANG']['tl_notification_template']['footer_html'] = ['Footer', 'Markup placed after the message body, e.g. an address and a legal notice.'];
$GLOBALS['TL_LANG']['tl_notification_template']['css'] = ['CSS', 'Styles for this layout, without the &lt;style&gt; tags. Written into style attributes before sending, which is what makes them work in Outlook and Gmail. @media rules cannot be inlined and are kept as a style block.'];
$GLOBALS['TL_LANG']['tl_notification_template']['inline_css'] = ['Inline the CSS', 'Copy every rule onto the elements it matches before sending. Leave this on unless you know the recipients\' client supports style blocks.'];
$GLOBALS['TL_LANG']['tl_notification_template']['published'] = ['Published', 'Make this layout selectable in messages.'];

$GLOBALS['TL_LANG']['tl_notification_template']['layout_mode_options'] = [
    'design' => 'A ready-made design',
    'header_footer' => 'Header and footer around the body',
    'wrapper' => 'Complete HTML document with ##message_body##',
];

$GLOBALS['TL_LANG']['tl_notification_template']['new'] = ['New layout', 'Create a new e-mail layout'];
$GLOBALS['TL_LANG']['tl_notification_template']['edit'] = ['Edit layout', 'Edit layout ID %s'];
$GLOBALS['TL_LANG']['tl_notification_template']['copy'] = ['Copy layout', 'Copy layout ID %s'];
$GLOBALS['TL_LANG']['tl_notification_template']['delete'] = ['Delete layout', 'Delete layout ID %s'];
$GLOBALS['TL_LANG']['tl_notification_template']['show'] = ['Layout details', 'Show the details of layout ID %s'];

$GLOBALS['TL_LANG']['tl_notification_template']['design'] = ['Design', 'Pick a ready-made design. Colours and the logo come from Branding, so every design is already on brand.'];

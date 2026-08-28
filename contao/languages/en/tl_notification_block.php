<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

$GLOBALS['TL_LANG']['tl_notification_block']['type_legend'] = 'Block';
$GLOBALS['TL_LANG']['tl_notification_block']['content_legend'] = 'Content';
$GLOBALS['TL_LANG']['tl_notification_block']['layout_legend'] = 'Spacing';
$GLOBALS['TL_LANG']['tl_notification_block']['publish_legend'] = 'Visibility';

$GLOBALS['TL_LANG']['tl_notification_block']['type'] = ['Block type', 'What this block is. Changing it shows that type\'s own settings.'];
$GLOBALS['TL_LANG']['tl_notification_block']['heading'] = ['Heading', 'The heading text. You can use simple tokens, e.g. ##name##.'];
$GLOBALS['TL_LANG']['tl_notification_block']['body_text'] = ['Text', 'Leave a blank line between paragraphs. Single line breaks become line breaks. Simple tokens such as ##name## work here.'];
$GLOBALS['TL_LANG']['tl_notification_block']['link_text'] = ['Link text', 'The label shown on the button or link.'];
$GLOBALS['TL_LANG']['tl_notification_block']['link_url'] = ['Link target', 'A full URL, a path starting with /, a mailto: address, an insert tag or a token.'];
$GLOBALS['TL_LANG']['tl_notification_block']['image'] = ['Image', 'Pick an image. JPG, PNG or GIF — mail clients do not render SVG or WebP reliably.'];
$GLOBALS['TL_LANG']['tl_notification_block']['image_alt'] = ['Alternative text', 'Shown when images are blocked, which is what most recipients see first.'];
$GLOBALS['TL_LANG']['tl_notification_block']['image_width'] = ['Image width (px)', 'Up to 536, the usable width inside the layout. Wider values are reduced.'];
$GLOBALS['TL_LANG']['tl_notification_block']['align'] = ['Alignment', 'How this block is aligned within the message.'];
$GLOBALS['TL_LANG']['tl_notification_block']['space_after'] = ['Space below (px)', 'Gap between this block and the next one.'];
$GLOBALS['TL_LANG']['tl_notification_block']['published'] = ['Visible', 'Include this block in the message.'];

$GLOBALS['TL_LANG']['tl_notification_block']['type_options'] = [
    'heading' => 'Heading',
    'paragraph' => 'Text',
    'list' => 'List',
    'details' => 'Detail table',
    'token' => 'Generated content',
    'button' => 'Button',
    'image' => 'Image',
    'teaser' => 'Image with text',
    'divider' => 'Divider',
    'html' => 'Custom HTML',
];

$GLOBALS['TL_LANG']['tl_notification_block']['text_style'] = ['Style', 'Normal body text, a larger lead paragraph, or smaller muted small print.'];
$GLOBALS['TL_LANG']['tl_notification_block']['text_style_options'] = [
    'normal' => 'Normal',
    'lead' => 'Lead',
    'muted' => 'Small print',
];

$GLOBALS['TL_LANG']['tl_notification_block']['list_style'] = ['List type', 'Bulleted or numbered.'];
$GLOBALS['TL_LANG']['tl_notification_block']['list_style_options'] = [
    'bullet' => 'Bulleted',
    'number' => 'Numbered',
];

$GLOBALS['TL_LANG']['tl_notification_block']['button_style'] = ['Button style', 'Filled with the brand colour, or outlined.'];
$GLOBALS['TL_LANG']['tl_notification_block']['button_style_options'] = [
    'solid' => 'Filled',
    'outline' => 'Outlined',
];

$GLOBALS['TL_LANG']['tl_notification_block']['teaser_layout'] = ['Arrangement', 'Where the image sits. On a narrow screen the two columns stack automatically.'];
$GLOBALS['TL_LANG']['tl_notification_block']['teaser_layout_options'] = [
    'image_left' => 'Image left',
    'image_right' => 'Image right',
    'image_top' => 'Image above',
];

$GLOBALS['TL_LANG']['tl_notification_block']['details_rows'] = ['Rows', 'A label and a value per row. Tokens such as ##email## work in either.'];
$GLOBALS['TL_LANG']['tl_notification_block']['token_name'] = ['Content', 'Which generated content to insert, e.g. the full list of submitted form fields.'];
$GLOBALS['TL_LANG']['tl_notification_block']['custom_html'] = ['HTML', 'Markup inserted as written. Use table-based markup for e-mail. A <style> block here would affect the whole message and is removed.'];

$GLOBALS['TL_LANG']['tl_notification_block']['heading_level'] = ['Size', 'How prominent the heading is.'];
$GLOBALS['TL_LANG']['tl_notification_block']['heading_level_options'] = [
    'h1' => 'Large',
    'h2' => 'Medium',
    'h3' => 'Small',
];

$GLOBALS['TL_LANG']['tl_notification_block']['align_options'] = [
    'left' => 'Left',
    'center' => 'Centred',
    'right' => 'Right',
];

$GLOBALS['TL_LANG']['tl_notification_block']['new'] = ['New block', 'Add a block'];
$GLOBALS['TL_LANG']['tl_notification_block']['edit'] = ['Edit block', 'Edit block ID %s'];
$GLOBALS['TL_LANG']['tl_notification_block']['copy'] = ['Duplicate block', 'Duplicate block ID %s'];
$GLOBALS['TL_LANG']['tl_notification_block']['cut'] = ['Move block', 'Move block ID %s'];
$GLOBALS['TL_LANG']['tl_notification_block']['delete'] = ['Delete block', 'Delete block ID %s'];
$GLOBALS['TL_LANG']['tl_notification_block']['show'] = ['Block details', 'Show the details of block ID %s'];
$GLOBALS['TL_LANG']['tl_notification_block']['toggle'] = ['Show/hide block', 'Show or hide block ID %s'];

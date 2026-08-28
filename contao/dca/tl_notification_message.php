<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_notification_message'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_notification',
        // ctable makes DC_Table::copy() call copyChildren(), so duplicating a message
        // duplicates its blocks, and deleting one deletes them.
        'ctable' => ['tl_notification_block'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        // This table has no "sorting" column, so nothing should ever offer a cut operation
        'notSortable' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['language'],
            'headerFields' => ['title', 'alias', 'tstamp'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['language', 'subject'],
            'format' => '%s <span style="color:#999;padding-left:3px">[%s]</span>',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        // String entries reuse Contao's default operation of that name, which is how
        // "children" (the blocks of this message) appears now that ctable is set. Spelling
        // them out as arrays would suppress every default, including children.
        'operations' => [
            'edit',
            'children',
            'preview' => [
                'icon' => 'preview.svg',
            ],
            'testsend' => [
                'icon' => 'resend.svg',
            ],
            'copy',
            'delete',
            'show',
        ],
    ],
    // 'default' must stay identical to 'html': PaletteBuilder falls back to it whenever the
    // selector value matches no palette, which is what an existing row with an empty
    // body_mode would do.
    'palettes' => [
        '__selector__' => ['body_mode'],
        'default' => '{gateway_legend},gateway,language,fallback;{content_legend},subject,text,auto_plaintext;{html_legend},template,body_mode,html,embed_images;{recipients_legend},recipients,cc,bcc,reply_to,priority;{attachment_legend},attachments;{publish_legend},published,start,stop',
        'html' => '{gateway_legend},gateway,language,fallback;{content_legend},subject,text,auto_plaintext;{html_legend},template,body_mode,html,embed_images;{recipients_legend},recipients,cc,bcc,reply_to,priority;{attachment_legend},attachments;{publish_legend},published,start,stop',
        'blocks' => '{gateway_legend},gateway,language,fallback;{content_legend},subject,text,auto_plaintext;{html_legend},template,body_mode,embed_images;{recipients_legend},recipients,cc,bcc,reply_to,priority;{attachment_legend},attachments;{publish_legend},published,start,stop',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'pid' => [
            'foreignKey' => 'tl_notification.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'gateway' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_notification_gateway.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'language' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'fallback' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'subject' => [
            'exclude' => true,
            'inputType' => 'text',
            // helpwizard: TokenHelpListener puts the available token list on this field too,
            // and without it Contao renders no icon to open it.
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'helpwizard' => true, 'tl_class' => 'long'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        // Not mandatory: a message may be HTML-only, or have its text part generated from
        // the HTML (see tl_notification_message.auto_plaintext). MessageRenderer rejects a
        // message that ends up with neither part.
        'text' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'ace', 'decodeEntities' => true, 'helpwizard' => true, 'tl_class' => 'clr long'],
            'sql' => "text NULL",
        ],
        'auto_plaintext' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'body_mode' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['blocks', 'html'],
            'reference' => &$GLOBALS['TL_LANG']['tl_notification_message']['body_mode_options'],
            // The DCA default applies only to records created through the backend
            // (DC_Table::create()), while the SQL default is what existing rows and
            // programmatically created ones get. That split is the whole back-compat story:
            // new messages are block-first, everything already in the database keeps its
            // HTML body and the identical render path.
            'default' => 'blocks',
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(16) NOT NULL default 'html'",
        ],
        'template' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_notification_template.title',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50 clr'],
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        // preserveTags/useRawRequestData keep pasted markup byte-for-byte: Contao would
        // otherwise strip tags and encode entities, which mangles a designer's HTML.
        'html' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => [
                'rte' => 'ace|html',
                'preserveTags' => true,
                'useRawRequestData' => true,
                'decodeEntities' => true,
                'class' => 'monospace',
                'tl_class' => 'clr long',
                'helpwizard' => true,
            ],
            'sql' => "text NULL",
        ],
        'embed_images' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        // Deliberately not mandatory: only some gateways address recipients at all (see
        // GatewayInterface::addressesRecipients()), and a webhook message would otherwise
        // have to invent an e-mail address to pass validation. Making it conditional on the
        // gateway would not work either -- a new record has no gateway chosen yet, so the
        // field would be required before the answer is even knowable. A message that does
        // need an address and has none is flagged in the list instead, by
        // MessageListener::findProblem(), which is where every other undeliverable
        // configuration is already reported.
        'recipients' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'long clr'],
            'sql' => "text NULL",
        ],
        'cc' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'long clr'],
            'sql' => "text NULL",
        ],
        'bcc' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'long clr'],
            'sql' => "text NULL",
        ],
        'reply_to' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'priority' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => [1, 2, 3, 4, 5],
            'reference' => &$GLOBALS['TL_LANG']['tl_notification_message']['priority_options'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "smallint(2) unsigned NOT NULL default 3",
        ],
        'attachments' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'isSortable' => true, 'files' => true, 'tl_class' => 'clr'],
            'sql' => "blob NULL",
        ],
        'published' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default '1'",
        ],
        'start' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];

<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_simple_message'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_simple_notification',
        'switchToEdit' => true,
        'enableVersioning' => true,
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
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'preview' => [
                'icon' => 'preview.svg',
            ],
            'testsend' => [
                'icon' => 'resend.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{gateway_legend},gateway,language,fallback;{content_legend},subject,text,auto_plaintext;{html_legend},template,html,embed_images;{recipients_legend},recipients,cc,bcc,reply_to,priority;{attachment_legend},attachments;{publish_legend},published,start,stop',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'pid' => [
            'foreignKey' => 'tl_simple_notification.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'gateway' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_simple_gateway.title',
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
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        // Not mandatory: a message may be HTML-only, or have its text part generated from
        // the HTML (see tl_simple_message.auto_plaintext). MessageRenderer rejects a
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
        'template' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_simple_template.title',
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
        'recipients' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'long clr'],
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
            'reference' => &$GLOBALS['TL_LANG']['tl_simple_message']['priority_options'],
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

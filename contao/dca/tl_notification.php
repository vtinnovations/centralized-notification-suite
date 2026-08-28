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
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;

$GLOBALS['TL_DCA']['tl_notification'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_notification_message'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'unique',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['type', 'title'],
            'headerFields' => ['title', 'alias', 'type'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
            'defaultSearchField' => 'title',
        ],
        'label' => [
            'fields' => ['title', 'alias'],
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
                'href' => 'table=tl_notification_message',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
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
        'default' => '{title_legend},title,alias,type',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        // Left blank, AliasListener generates it from the title. This is the identifier
        // code passes to CentralizedNotificationSuite::send(), so it stays editable.
        'alias' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        // Decides which tokens the message editor offers and which triggers list this
        // notification. Existing records default to "custom", which offers no assumptions.
        'type' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => NotificationModel::TYPES,
            'reference' => &$GLOBALS['TL_LANG']['tl_notification']['type_options'],
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(32) NOT NULL default 'custom'",
        ],
    ],
];

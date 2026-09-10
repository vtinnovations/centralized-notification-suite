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

$GLOBALS['TL_DCA']['tl_notification_branding'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        // A singleton: BrandingListener redirects straight into the one record, so the
        // list view is never shown and there is nothing to create, copy or delete.
        'closed' => true,
        'notCreatable' => true,
        'notDeletable' => true,
        'notCopyable' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    // The list view is never reached (BrandingListener redirects into the record), but it
    // cannot be omitted: DataContainer::addPtableTags() reads list.sorting.mode unguarded
    // when saving, and a missing key raises a warning that aborts the save.
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['id'],
        ],
        'label' => [
            'fields' => ['company_name'],
            'format' => '%s',
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{logo_legend},logo,logo_width;{colour_legend},brand_color;{company_legend},company_name,website,support_email,address;{footer_legend},footer_note',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'logo' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            // A single file, and only the formats mail clients actually render. SVG is
            // deliberately absent: Outlook and Gmail both drop it.
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'jpg,jpeg,png,gif', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'logo_width' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'maxlength' => 4, 'tl_class' => 'w50'],
            'sql' => "smallint(5) unsigned NOT NULL default '140'",
        ],
        'brand_color' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 7, 'colorpicker' => true, 'isHexColor' => true, 'decodeEntities' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(7) NOT NULL default '#0b5fff'",
        ],
        'company_name' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'website' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'support_email' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'email', 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'address' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['decodeEntities' => true, 'rows' => 3, 'tl_class' => 'clr long'],
            'sql' => 'text NULL',
        ],
        'footer_note' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['decodeEntities' => true, 'rows' => 3, 'tl_class' => 'clr long'],
            'sql' => 'text NULL',
        ],
    ],
];

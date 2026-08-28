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

/**
 * The blocks a message body is composed of.
 *
 * Shared columns are declared here rather than contributed by the block classes: several
 * types want a "heading" or an "image", and letting the first registered type claim the
 * column (the way GatewayDcaListener arbitrates with ??=) would make the schema depend on
 * service registration order. Only genuinely type-specific fields come from the classes --
 * see BlockDcaListener.
 */
$GLOBALS['TL_DCA']['tl_notification_block'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_notification_message',
        'enableVersioning' => true,
        'markAsCopy' => 'heading',
        // notSortable is deliberately absent: DC_Table only offers dragging when it is falsy.
        'sql' => [
            'keys' => [
                'id' => 'primary',
                // Matches BlockModel::findPublishedByPid()'s WHERE plus its ORDER BY
                'pid,published,sorting' => 'index',
                'tstamp' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            // Drag-reordering needs all three of these together: MODE_PARENT routes rendering
            // to DC_Table::parentView(), fields[0] must be the literal 'sorting', and
            // config.notSortable must be falsy. Change any one and the drag handle disappears.
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting'],
            'headerFields' => ['subject', 'language', 'template', 'tstamp'],
            'panelLayout' => 'search,limit',
            'defaultSearchField' => 'heading',
            'renderAsGrid' => true,
            'limitHeight' => 160,
        ],
        'label' => [
            'fields' => ['type'],
            'format' => '%s',
            // label_callback is attached by BlockDcaListener::formatLabel()
        ],
        // No 'operations' and no 'global_operations' on purpose. Contao's
        // DefaultOperationsListener rebuilds list.operations on loadDataContainer and only
        // adds "cut" -- which is what pastes a dragged row -- when ptable is set and
        // notSortable is falsy. Declaring operations with array values suppresses those
        // defaults entirely, which is exactly why tl_notification_message has no cut today.
    ],
    'palettes' => [
        '__selector__' => ['type'],
        // One palette per registered type is added by BlockDcaListener
        'default' => '{type_legend},type',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_notification_message.subject',
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'sorting' => [
            // Maintained by cut/paste, never edited directly
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'type' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            // Options come from BlockDcaListener::getTypeOptions(); submitOnChange is set
            // there too, so the palette swaps when the type changes.
            'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['type_options'],
            'eval' => ['mandatory' => true, 'chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default 'paragraph'",
        ],
        'heading' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'body_text' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            // A plain textarea, not an RTE: the block renderer owns every byte of markup, so
            // the output is email-safe by construction and there is nothing to sanitise.
            'eval' => ['decodeEntities' => true, 'rows' => 6, 'helpwizard' => true, 'tl_class' => 'clr long'],
            'sql' => 'text NULL',
        ],
        'link_text' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'link_url' => [
            'exclude' => true,
            'inputType' => 'text',
            // No rgxp: an insert tag or a ##token## is a legitimate value here, and
            // AbstractBlock::safeUrl() is what actually guards the scheme at render time.
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'image' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            // jpg/jpeg/png/gif only. WebP and SVG are in ImageEmbedder::EMBEDDABLE, so the
            // pipeline would cheerfully inline a file that renders as nothing in Outlook and
            // Gmail, with no error anywhere to explain it.
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'jpg,jpeg,png,gif', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'image_alt' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'image_width' => [
            'exclude' => true,
            'inputType' => 'text',
            // Clamped to the real content width at render time: the design's .sn-body has
            // 32px of horizontal padding inside a 600px card, so 536 is the usable maximum.
            'eval' => ['rgxp' => 'natural', 'maxlength' => 4, 'tl_class' => 'w50'],
            'sql' => "smallint(5) unsigned NOT NULL default 536",
        ],
        'align' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['left', 'center', 'right'],
            'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['align_options'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'left'",
        ],
        'space_after' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'maxlength' => 3, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 20',
        ],
        'published' => [
            'exclude' => true,
            'filter' => true,
            'toggle' => true,
            'inputType' => 'checkbox',
            // char(1) for consistency with the bundle's other published columns. Note
            // DC_Table::toggle() writes PHP false, which lands as '0' rather than '' -- so
            // every read must be truthiness-based and SQL must compare against '1'.
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default '1'",
        ],
    ],
];

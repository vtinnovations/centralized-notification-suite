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
 * Delivery history. Read-only by design: the log records what happened, so editing it
 * would only ever make it lie. Records are created by SendLogListener, removed by the
 * prune cron job, and can be re-sent from the list.
 */
$GLOBALS['TL_DCA']['tl_notification_log'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'closed' => true,
        'notEditable' => true,
        'notCopyable' => true,
        'notSortable' => false,
        'enableVersioning' => false,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'status' => 'index',
                'tstamp' => 'index',
                // Index, not unique: a row written by a code path that had no reference
                // would otherwise collide with every other such row on the empty string.
                'reference' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['tstamp DESC'],
            'flag' => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;search,limit',
            'defaultSearchField' => 'recipients',
        ],
        'label' => [
            'fields' => ['status', 'tstamp', 'alias', 'subject', 'recipients'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'clear' => [
                'href' => 'key=clear',
                'icon' => 'delete.svg',
                'class' => 'header_icon',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['tl_notification_log']['clearConfirm'] ?? '') . '\'))return false"',
            ],
        ],
        'operations' => [
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
            'resend' => [
                'icon' => 'resend.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{details_legend},status,error,alias,gateway_type,subject,recipients;{content_legend},body_text,body_html',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_notification_log']['tstamp'],
            'sorting' => true,
            'flag' => DataContainer::SORT_DAY_DESC,
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'pid' => [
            'foreignKey' => 'tl_notification.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'message' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        // Travels with the e-mail as X-Simple-Notify-Ref so the Messenger worker can find
        // this row again and record whether the transport actually delivered it.
        'reference' => [
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'alias' => [
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'gateway' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'gateway_type' => [
            'filter' => true,
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'recipients' => [
            'search' => true,
            'sql' => "text NULL",
        ],
        'subject' => [
            'search' => true,
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'status' => [
            'filter' => true,
            'sorting' => true,
            'reference' => &$GLOBALS['TL_LANG']['tl_notification_log']['status_options'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'error' => [
            'sql' => "text NULL",
        ],
        'body_html' => [
            'sql' => "mediumtext NULL",
        ],
        'body_text' => [
            'sql' => "mediumtext NULL",
        ],
        // Everything needed to replay the message that has no column of its own: cc, bcc,
        // reply_to, priority and the token set. Kept as one JSON column so adding an
        // envelope field later does not need a schema change.
        'envelope' => [
            'sql' => "mediumblob NULL",
        ],
        'attempts' => [
            'sql' => "smallint(5) unsigned NOT NULL default 0",
        ],
        'source' => [
            'filter' => true,
            'reference' => &$GLOBALS['TL_LANG']['tl_notification_log']['source_options'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'last_attempt' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
    ],
];

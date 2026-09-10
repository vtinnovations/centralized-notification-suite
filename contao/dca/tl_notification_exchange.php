<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

declare(strict_types=1);

/*
 * Bookkeeping for server-initiated updates: which requests have already been processed.
 *
 * Deliberately has no backend module and no palettes. It is not content, nobody edits it, and
 * it is declared here only so that contao:migrate creates and maintains the table like any
 * other. Nothing in it is authoritative state -- the record itself lives on disk.
 */
$GLOBALS['TL_DCA']['tl_notification_exchange'] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary',
                // Idempotency is looked up by request id on every inbound call
                'request_id' => 'unique',
                // A nonce is single-use by definition, and the database is what enforces it.
                // Without this index a captured request could be replayed under a fresh
                // request id and would look like a brand new call.
                'nonce_digest' => 'unique',
                'seen' => 'index',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'request_id' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        // A digest, never the body: the body carries a full key and a signed payload
        'body_digest' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'nonce_digest' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'applied_version' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'seen' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
    ],
];

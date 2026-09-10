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

namespace VTInnovations\CentralizedNotificationSuite\Block;

use VTInnovations\CentralizedNotificationSuite\Message\Branding;

/**
 * A headline.
 *
 * margin:0 is deliberate and inline: the design's stylesheet carries
 * ".sn-body h1,h2,h3 { margin:0 0 12px }", and because the CSS inliner never overwrites an
 * existing inline property, setting it here keeps all vertical rhythm under the block's own
 * "space below" setting instead of two sources fighting over it.
 */
class HeadingBlock extends AbstractBlock
{
    private const LEVELS = ['h1' => 28, 'h2' => 22, 'h3' => 17];

    public function getName(): string
    {
        return 'heading';
    }

    public function getGroup(): string
    {
        return 'Text';
    }

    public function getConfigFields(): array
    {
        return [
            'heading_level' => [
                'exclude' => true,
                'inputType' => 'select',
                'options' => array_keys(self::LEVELS),
                'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['heading_level_options'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(2) NOT NULL default 'h2'",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},heading,heading_level';
    }

    public function render(array $row, Branding $branding): string
    {
        $text = trim((string) ($row['heading'] ?? ''));

        if ('' === $text) {
            return '';
        }

        $level = isset(self::LEVELS[$row['heading_level'] ?? '']) ? (string) $row['heading_level'] : 'h2';

        $heading = \sprintf(
            '<%1$s style="margin:0;padding:0;font-size:%2$dpx;line-height:1.25;font-weight:700;color:#11181c;mso-line-height-rule:exactly">%3$s</%1$s>',
            $level,
            self::LEVELS[$level],
            $this->escape($text),
        );

        return $this->row($heading, $this->align($row));
    }
}

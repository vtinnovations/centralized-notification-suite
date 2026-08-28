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
 * Body copy.
 *
 * A plain textarea rather than a rich-text editor: the renderer owns every byte of markup,
 * so the output is Outlook-safe by construction and there is no pasted-Word styling to strip.
 * Blank lines separate paragraphs, single newlines become line breaks.
 */
class ParagraphBlock extends AbstractBlock
{
    /** style key => [font size, colour] */
    private const STYLES = [
        'normal' => [15, '#1f2933'],
        'lead' => [17, '#1f2933'],
        'muted' => [13, '#69737d'],
    ];

    public function getName(): string
    {
        return 'paragraph';
    }

    public function getGroup(): string
    {
        return 'Text';
    }

    public function getConfigFields(): array
    {
        return [
            'text_style' => [
                'exclude' => true,
                'inputType' => 'select',
                'options' => array_keys(self::STYLES),
                'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['text_style_options'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default 'normal'",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},body_text,text_style';
    }

    public function render(array $row, Branding $branding): string
    {
        $text = (string) ($row['body_text'] ?? '');

        if ('' === trim($text)) {
            return '';
        }

        $style = isset(self::STYLES[$row['text_style'] ?? '']) ? (string) $row['text_style'] : 'normal';
        [$size, $colour] = self::STYLES[$style];

        // mso-line-height-rule:exactly because Outlook otherwise treats line-height as a
        // minimum and the rhythm drifts
        $paragraphStyle = \sprintf(
            'margin:0 0 12px;font-size:%dpx;line-height:1.6;color:%s;mso-line-height-rule:exactly',
            $size,
            $colour,
        );

        return $this->row($this->paragraphs($text, $paragraphStyle), $this->align($row));
    }
}

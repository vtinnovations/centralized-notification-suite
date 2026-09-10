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
 * A bulleted or numbered list, one item per line.
 */
class ListBlock extends AbstractBlock
{
    public function getName(): string
    {
        return 'list';
    }

    public function getGroup(): string
    {
        return 'Text';
    }

    public function getConfigFields(): array
    {
        return [
            'list_style' => [
                'exclude' => true,
                'inputType' => 'select',
                'options' => ['bullet', 'number'],
                'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['list_style_options'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default 'bullet'",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},body_text,list_style';
    }

    public function render(array $row, Branding $branding): string
    {
        $items = preg_split('/\R+/', trim((string) ($row['body_text'] ?? ''))) ?: [];
        $items = array_values(array_filter(array_map('trim', $items), static fn (string $i): bool => '' !== $i));

        if (!$items) {
            return '';
        }

        $tag = 'number' === ($row['list_style'] ?? 'bullet') ? 'ol' : 'ul';
        $li = '';

        foreach ($items as $item) {
            $li .= \sprintf(
                '<li style="margin:0 0 6px;font-size:15px;line-height:1.6;color:#1f2933;mso-line-height-rule:exactly">%s</li>',
                $this->escape($item),
            );
        }

        // padding-left rather than margin: Outlook drops the list indent otherwise
        $list = \sprintf('<%1$s style="margin:0;padding:0 0 0 22px">%2$s</%1$s>', $tag, $li);

        return $this->row($list, $this->align($row));
    }
}

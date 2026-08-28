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
 * An image beside a heading, text and optional link.
 *
 * Built as a fluid-hybrid rather than a two-cell table, because inline styles beat media
 * queries in the client: the columns are inline-block with width:100% and a max-width, so
 * they sit side by side while there is room and wrap to stacked on their own when there is
 * not. That means stacking still works on a message with no design assigned, where none of
 * the layout's media queries exist.
 *
 * Two things here are load-bearing rather than decorative:
 *  - font-size:0 on the wrapper. The CSS inliner pretty-prints its output, which puts a
 *    newline between the two columns; that renders as a word space and pushes the second
 *    column onto its own line exactly at the width where both should still fit.
 *  - the conditional-comment ghost table. Outlook ignores display:inline-block on a div, so
 *    without it both columns would stack there permanently.
 */
class TeaserBlock extends AbstractBlock
{
    private const GUTTER = 16;

    public function __construct(private readonly FileUrlResolver $files)
    {
    }

    public function getName(): string
    {
        return 'teaser';
    }

    public function getGroup(): string
    {
        return 'Media';
    }

    public function getConfigFields(): array
    {
        return [
            'teaser_layout' => [
                'exclude' => true,
                'inputType' => 'select',
                'options' => ['image_left', 'image_right', 'image_top'],
                'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['teaser_layout_options'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default 'image_left'",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},image,image_alt,heading,body_text,link_text,link_url,teaser_layout';
    }

    public function render(array $row, Branding $branding): string
    {
        $src = $this->files->resolve($row['image'] ?? null);
        $copy = $this->copy($row, $branding);

        if ('' === $copy && '' === $src) {
            return '';
        }

        $layout = \in_array($row['teaser_layout'] ?? '', ['image_left', 'image_right', 'image_top'], true)
            ? (string) $row['teaser_layout']
            : 'image_left';

        // Nothing to sit beside, so fall back to a single stacked column
        if ('' === $src || 'image_top' === $layout) {
            $stacked = ('' !== $src ? $this->image($src, $row, self::CONTENT_WIDTH) : '').$copy;

            return $this->row($stacked, 'left');
        }

        $imageWidth = 180;
        $textWidth = self::CONTENT_WIDTH - $imageWidth - self::GUTTER;

        $imageCol = $this->column($this->image($src, $row, $imageWidth), $imageWidth);
        $textCol = $this->column($copy, $textWidth);

        [$first, $second] = 'image_right' === $layout ? [$textCol, $imageCol] : [$imageCol, $textCol];
        [$firstW, $secondW] = 'image_right' === $layout ? [$textWidth, $imageWidth] : [$imageWidth, $textWidth];

        return \sprintf(
            '<div class="sn-blk-cols" style="font-size:0;text-align:left">'
            .'<!--[if mso]><table role="presentation" width="%1$d" cellpadding="0" cellspacing="0" border="0"><tr><td width="%2$d" valign="top"><![endif]-->'
            .'%3$s'
            .'<!--[if mso]></td><td width="%4$d">&nbsp;</td><td width="%5$d" valign="top"><![endif]-->'
            .'%6$s'
            .'<!--[if mso]></td></tr></table><![endif]-->'
            .'</div>',
            self::CONTENT_WIDTH,
            $firstW,
            $first,
            self::GUTTER,
            $secondW,
            $second,
        );
    }

    private function column(string $content, int $width): string
    {
        return \sprintf(
            '<div class="sn-blk-col" style="display:inline-block;width:100%%;max-width:%dpx;vertical-align:top;font-size:15px;text-align:left">%s</div>',
            $width,
            $content,
        );
    }

    private function image(string $src, array $row, int $width): string
    {
        return \sprintf(
            '<img src="%s" width="%d" alt="%s" style="display:block;width:100%%;max-width:%dpx;height:auto;border:0;outline:none;text-decoration:none">',
            $this->escape($src),
            $width,
            $this->escape((string) ($row['image_alt'] ?? '')),
            $width,
        );
    }

    private function copy(array $row, Branding $branding): string
    {
        $out = '';

        if ('' !== ($heading = trim((string) ($row['heading'] ?? '')))) {
            $out .= \sprintf(
                '<p style="margin:0 0 6px;font-size:17px;line-height:1.3;font-weight:700;color:#11181c">%s</p>',
                $this->escape($heading),
            );
        }

        if ('' !== trim((string) ($row['body_text'] ?? ''))) {
            $out .= $this->paragraphs(
                (string) $row['body_text'],
                'margin:0 0 8px;font-size:15px;line-height:1.6;color:#1f2933;mso-line-height-rule:exactly',
            );
        }

        $label = trim((string) ($row['link_text'] ?? ''));
        $url = $this->safeUrl((string) ($row['link_url'] ?? ''));

        if ('' !== $label && '' !== $url) {
            $out .= \sprintf(
                '<p style="margin:0"><a href="%s" style="font-size:15px;font-weight:600;color:%s;text-decoration:underline">%s</a></p>',
                $this->escape($url),
                $branding->brandColor,
                $this->escape($label),
            );
        }

        return $out;
    }
}

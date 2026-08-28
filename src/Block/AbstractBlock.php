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

/**
 * Shared markup helpers for blocks.
 *
 * The primitives mirror DesignLibrary's: a presentation table wrapper, and escaping applied
 * at the point a stored value is interpolated into markup. Keeping them here means every
 * block emits the same Outlook-safe table shape rather than each reinventing it.
 */
abstract class AbstractBlock implements BlockInterface
{
    /**
     * The usable content width in pixels.
     *
     * The design's card is 600px and .sn-body adds 32px of padding either side, so anything
     * a block emits has 536px to live in. Emitting width="600" overflows the card.
     */
    public const CONTENT_WIDTH = 536;

    public function getGroup(): string
    {
        return 'Content';
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function getPalette(): string
    {
        return '';
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escaped, with single newlines becoming line breaks.
     */
    protected function multiline(string $value): string
    {
        return nl2br($this->escape($value), false);
    }

    /**
     * Escaped, with blank lines becoming paragraphs and single newlines line breaks.
     *
     * @param string $style Inline style applied to every paragraph
     */
    protected function paragraphs(string $value, string $style): string
    {
        $out = '';

        foreach (preg_split('/\R{2,}/', trim($value)) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);

            if ('' === $paragraph) {
                continue;
            }

            $out .= \sprintf('<p style="%s">%s</p>', $style, $this->multiline($paragraph));
        }

        return $out;
    }

    /**
     * The one structural primitive: a full-width presentation table with a single cell.
     *
     * The cellpadding/cellspacing/border attributes are not redundant with CSS -- Outlook's
     * rendering engine ignores border-collapse and uses cellspacing instead.
     */
    protected function row(string $content, string $align = 'left', string $tdStyle = '', string $class = ''): string
    {
        if ('' === $content) {
            return '';
        }

        return \sprintf(
            '<table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt">'
            .'<tr><td%s align="%s"%s>%s</td></tr></table>',
            '' !== $class ? ' class="'.$class.'"' : '',
            $align,
            '' !== $tdStyle ? ' style="'.$tdStyle.'"' : '',
            $content,
        );
    }

    protected function align(array $row): string
    {
        $align = (string) ($row['align'] ?? 'left');

        return \in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
    }

    /**
     * A link target safe to put in an href.
     *
     * Contao's input sanitisation keeps href values verbatim, so "javascript:" survives it.
     * Insert tags and ##tokens## are allowed through unchanged because both are legitimate
     * here and are resolved later in the pipeline.
     */
    protected function safeUrl(string $url): string
    {
        $url = trim($url);

        if ('' === $url) {
            return '';
        }

        // Resolved later by the insert tag parser or the token parser
        if (str_starts_with($url, '{{') || str_contains($url, '##')) {
            return $url;
        }

        if (preg_match('#^(?:https?://|mailto:|tel:)#i', $url) || preg_match('#^[/?\#]#', $url)) {
            return $url;
        }

        return '#';
    }

    /**
     * Image width, clamped to what actually fits inside the card.
     */
    protected function imageWidth(array $row): int
    {
        $width = (int) ($row['image_width'] ?? self::CONTENT_WIDTH);

        return max(1, min(self::CONTENT_WIDTH, 0 !== $width ? $width : self::CONTENT_WIDTH));
    }
}

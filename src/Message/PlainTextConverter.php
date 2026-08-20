<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

/**
 * Derives the plain text part from the HTML body.
 *
 * A multipart message still needs a text alternative: some clients prefer it, plain-text
 * readers depend on it, and spam filters treat HTML-only mail with suspicion. Writing it by
 * hand means maintaining the same content twice and quietly letting the two drift apart.
 */
class PlainTextConverter
{
    public function convert(string $html): string
    {
        if ('' === trim($html)) {
            return '';
        }

        $text = $html;

        // Drop anything whose contents are not readable prose. The preheader is included:
        // it exists only to fill the inbox preview and would read as a duplicate here.
        $text = (string) preg_replace('#<(script|style|head|title)\b[^>]*>.*?</\1>#is', '', $text);
        $text = (string) preg_replace('#<div[^>]*display:\s*none[^>]*>.*?</div>#is', '', $text);

        // Keep link targets, which are the one thing stripping tags always loses. A link
        // whose text already is the URL needs no repetition.
        $text = (string) preg_replace_callback(
            '#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            static function (array $m): string {
                $url = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $label = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ('' === $label || $label === $url) {
                    return $url;
                }

                if (str_starts_with($url, 'mailto:') && substr($url, 7) === $label) {
                    return $label;
                }

                return \sprintf('%s (%s)', $label, $url);
            },
            $text,
        );

        // Turn structure into line breaks before the tags are gone
        $text = (string) preg_replace('#<(br)\s*/?>#i', "\n", $text);
        $text = (string) preg_replace('#</(p|div|tr|h[1-6]|li|blockquote)>#i', "\n\n", $text);
        $text = (string) preg_replace('#<li\b[^>]*>#i', '- ', $text);
        $text = (string) preg_replace('#</(td|th)>#i', "\t", $text);
        $text = (string) preg_replace('#<hr\s*/?>#i', "\n---\n", $text);

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalise the whitespace the markup left behind
        $text = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $text);
        $text = (string) preg_replace('#[ \t]*\n[ \t]*#', "\n", $text);
        $text = (string) preg_replace('#[ \t]{2,}#', ' ', $text);
        $text = (string) preg_replace('#\n{3,}#', "\n\n", $text);

        return trim($text);
    }
}

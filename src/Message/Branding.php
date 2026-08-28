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

namespace VTInnovations\CentralizedNotificationSuite\Message;

/**
 * The brand values a design needs, as plain strings.
 *
 * A value object rather than the model: DesignLibrary has to be renderable without a booted
 * framework (the template preview builds one by hand), and every design needs the derived
 * shades below, which are a property of the brand colour rather than of the database row.
 */
class Branding
{
    /**
     * Always carries a leading "#": Contao's colour picker stores the hex without one, and
     * a bare "dcca87" in a CSS declaration is simply ignored by the mail client.
     */
    public readonly string $brandColor;

    public function __construct(
        public readonly string $logoUrl = '',
        public readonly int $logoWidth = 140,
        string $brandColor = '#0b5fff',
        public readonly string $companyName = '',
        public readonly string $website = '',
        public readonly string $supportEmail = '',
        public readonly string $address = '',
        public readonly string $footerNote = '',
    ) {
        $this->brandColor = self::normaliseHex($brandColor);
    }

    /**
     * Accepts "#abc", "abc", "#aabbcc" and "aabbcc"; anything else falls back to the
     * default, so a half-typed colour cannot take the whole layout down with it.
     */
    public static function normaliseHex(string $color, string $fallback = '#0b5fff'): string
    {
        $hex = ltrim(trim($color), '#');

        if (!\in_array(\strlen($hex), [3, 6], true) || !ctype_xdigit($hex)) {
            return $fallback;
        }

        return '#'.strtolower($hex);
    }

    /**
     * A readable foreground for text placed on the brand colour. Uses the WCAG relative
     * luminance cut-off so a pale brand colour gets dark text instead of invisible white.
     */
    public function onBrandColor(): string
    {
        return $this->luminance($this->brandColor) > 0.55 ? '#11181c' : '#ffffff';
    }

    /**
     * The brand colour mixed towards white, for tinted panels and rules.
     */
    public function tint(float $amount = 0.92): string
    {
        return $this->mix($this->brandColor, '#ffffff', $amount);
    }

    /**
     * The brand colour mixed towards black, for dark headers and footers.
     */
    public function shade(float $amount = 0.55): string
    {
        return $this->mix($this->brandColor, '#000000', $amount);
    }

    /**
     * The company name, falling back to the host of the website so a footer is never blank.
     */
    public function displayName(): string
    {
        if ('' !== $this->companyName) {
            return $this->companyName;
        }

        return (string) (parse_url($this->website, PHP_URL_HOST) ?: '');
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (3 === \strlen($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (6 !== \strlen($hex) || !ctype_xdigit($hex)) {
            return [11, 95, 255];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = $this->rgb($hex);

        $channel = static function (int $value): float {
            $value /= 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }

    /**
     * @param float $amount 0 = all of $hex, 1 = all of $towards
     */
    private function mix(string $hex, string $towards, float $amount): string
    {
        $amount = max(0.0, min(1.0, $amount));
        [$r1, $g1, $b1] = $this->rgb($hex);
        [$r2, $g2, $b2] = $this->rgb($towards);

        return \sprintf(
            '#%02x%02x%02x',
            (int) round($r1 + ($r2 - $r1) * $amount),
            (int) round($g1 + ($g2 - $g1) * $amount),
            (int) round($b1 + ($b2 - $b1) * $amount),
        );
    }
}

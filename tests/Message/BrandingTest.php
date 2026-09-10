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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Message;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Message\Branding;

class BrandingTest extends TestCase
{
    /**
     * Contao's colour picker stores the hex without a leading "#", and a bare "dcca87" in a
     * CSS declaration is ignored by the mail client -- the whole design renders unstyled.
     */
    #[DataProvider('hexProvider')]
    public function testNormalisesTheBrandColour(string $input, string $expected): void
    {
        $this->assertSame($expected, (new Branding(brandColor: $input))->brandColor);
    }

    public static function hexProvider(): array
    {
        return [
            'without hash' => ['dcca87', '#dcca87'],
            'with hash' => ['#dcca87', '#dcca87'],
            'uppercase' => ['#ABCDEF', '#abcdef'],
            'shorthand' => ['abc', '#abc'],
            'surrounding space' => ['  #c8102e  ', '#c8102e'],
            'empty falls back' => ['', '#0b5fff'],
            'nonsense falls back' => ['nonsense', '#0b5fff'],
            'wrong length falls back' => ['#12345', '#0b5fff'],
            'non-hex digits fall back' => ['#gggggg', '#0b5fff'],
        ];
    }

    /**
     * Text on the brand colour has to stay readable, so a pale brand must not get white text.
     */
    public function testPicksAReadableForegroundForTheBrandColour(): void
    {
        $this->assertSame('#11181c', (new Branding(brandColor: '#ffe066'))->onBrandColor(), 'pale brand needs dark text');
        $this->assertSame('#ffffff', (new Branding(brandColor: '#0b5fff'))->onBrandColor(), 'dark brand needs light text');
    }

    public function testTintAndShadeStayValidHexAndMoveTowardsWhiteAndBlack(): void
    {
        $branding = new Branding(brandColor: '#808080');

        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $branding->tint());
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $branding->shade());
        $this->assertGreaterThan(0x80, hexdec(substr($branding->tint(), 1, 2)), 'tint is lighter');
        $this->assertLessThan(0x80, hexdec(substr($branding->shade(), 1, 2)), 'shade is darker');
    }

    public function testFallsBackToTheWebsiteHostWhenNoCompanyNameIsSet(): void
    {
        $this->assertSame('VT Innovations', (new Branding(companyName: 'VT Innovations', website: 'https://v-t.one'))->displayName());
        $this->assertSame('v-t.one', (new Branding(website: 'https://v-t.one/x'))->displayName());
        $this->assertSame('', (new Branding())->displayName());
    }
}

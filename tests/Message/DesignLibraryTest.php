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
use VTInnovations\CentralizedNotificationSuite\Message\DesignLibrary;
use VTInnovations\CentralizedNotificationSuite\Message\EmailLayout;
use VTInnovations\CentralizedNotificationSuite\Model\TemplateModel;

class DesignLibraryTest extends TestCase
{
    private DesignLibrary $designs;

    protected function setUp(): void
    {
        $this->designs = new DesignLibrary();
    }

    private function branding(): Branding
    {
        return new Branding(
            logoUrl: '/files/logo.png',
            logoWidth: 150,
            brandColor: '#c8102e',
            companyName: 'VT Innovations',
            website: 'https://v-t.one',
            supportEmail: 'support@v-t.one',
            address: "Street 1\n12345 Town",
            footerNote: 'You received this because you contacted us.',
        );
    }

    public static function designProvider(): array
    {
        return array_map(
            static fn (string $name): array => [$name],
            array_merge(...array_map('array_keys', array_values((new DesignLibrary())->getGroupedOptions()))),
        );
    }

    public function testShipsTheAdvertisedNumberOfDesigns(): void
    {
        $names = array_merge(...array_map('array_keys', array_values($this->designs->getGroupedOptions())));

        $this->assertGreaterThanOrEqual(15, \count($names), 'at least 15 designs are promised');
        $this->assertSame($names, array_unique($names), 'design names must be unique');
    }

    /**
     * Every design has to produce a document the body can actually be placed into, with
     * balanced tables -- unbalanced markup is what breaks Outlook specifically.
     */
    #[DataProvider('designProvider')]
    public function testEveryDesignProducesUsableMarkup(string $name): void
    {
        $layout = $this->designs->build($name, $this->branding());

        $this->assertSame(EmailLayout::MODE_WRAPPER, $layout->mode);
        $this->assertStringContainsString(TemplateModel::BODY_PLACEHOLDER, $layout->wrapperHtml);
        $this->assertNotSame('', trim($layout->css));
        $this->assertSame(
            substr_count($layout->wrapperHtml, '<table'),
            substr_count($layout->wrapperHtml, '</table>'),
            'tables must be balanced',
        );
        $this->assertSame(
            substr_count($layout->wrapperHtml, '<tr'),
            substr_count($layout->wrapperHtml, '</tr>'),
            'rows must be balanced',
        );
    }

    /**
     * A fixed 600px card overflows a phone and clips the footer.
     */
    #[DataProvider('designProvider')]
    public function testEveryDesignIsResponsive(string $name): void
    {
        $layout = $this->designs->build($name, $this->branding());

        $this->assertStringContainsString('max-width:600px', $layout->css);
        $this->assertStringContainsString('@media', $layout->css);
        $this->assertStringContainsString('width="600"', $layout->wrapperHtml, 'Outlook ignores max-width and needs the attribute');
    }

    public function testBrandingReachesTheMarkup(): void
    {
        $layout = $this->designs->build('brandbar-left', $this->branding());

        $this->assertStringContainsString('/files/logo.png', $layout->wrapperHtml);
        $this->assertStringContainsString('width="150"', $layout->wrapperHtml);
        $this->assertStringContainsString('VT Innovations', $layout->wrapperHtml);
        $this->assertStringContainsString('12345 Town', $layout->wrapperHtml);
        $this->assertStringContainsString('mailto:support@v-t.one', $layout->wrapperHtml);
        $this->assertStringContainsString('#c8102e', $layout->css);
    }

    /**
     * A colour stored without its "#" must not reach the stylesheet as a bare hex, which CSS
     * simply ignores.
     */
    public function testNeverEmitsABareHexColour(): void
    {
        $css = $this->designs->build('brandbar-left', new Branding(brandColor: 'dcca87'))->css;

        $this->assertStringContainsString('#dcca87', $css);
        $this->assertDoesNotMatchRegularExpression('/(?:background|color)\s*:\s*[0-9a-f]{6}\s*[;}]/i', $css);
    }

    public function testAnEmptyBrandingStillRendersRatherThanLeavingStraySeparators(): void
    {
        $layout = $this->designs->build('card-left', new Branding());

        $this->assertStringContainsString(TemplateModel::BODY_PLACEHOLDER, $layout->wrapperHtml);
        $this->assertStringNotContainsString('<img', $layout->wrapperHtml, 'no logo configured means no broken image');
        $this->assertStringNotContainsString('&middot;', $layout->wrapperHtml, 'no separator without values to separate');
    }

    /**
     * A design renamed or removed must not stop a notification from going out.
     */
    public function testAnUnknownDesignFallsBackInsteadOfThrowing(): void
    {
        $layout = $this->designs->build('does-not-exist', $this->branding());

        $this->assertStringContainsString(TemplateModel::BODY_PLACEHOLDER, $layout->wrapperHtml);
    }

    public function testMarkupIsEscaped(): void
    {
        $layout = $this->designs->build('card-left', new Branding(companyName: '<script>alert(1)</script>'));

        $this->assertStringNotContainsString('<script>', $layout->wrapperHtml);
        $this->assertStringContainsString('&lt;script&gt;', $layout->wrapperHtml);
    }

    /**
     * Blocks style themselves inline, which is what protects them from the design's own
     * selectors -- but an inline style cannot be undone by a media query, so the two column
     * classes are the only hook mobile stacking has. Every design must therefore carry them,
     * and must override them with !important.
     */
    #[DataProvider('designProvider')]
    public function testEveryDesignCanReclaimBlockColumnsOnMobile(string $name): void
    {
        $css = $this->designs->build($name, $this->branding())->css;

        $this->assertStringContainsString('.sn-blk-col', $css);
        $this->assertMatchesRegularExpression(
            '/@media.*?\.sn-blk-col\s*\{[^}]*!important/is',
            $css,
            'the mobile override needs !important to beat the inlined styles',
        );
    }

    /**
     * Without a head the output ends up with a <style> block ahead of the doctype, which puts
     * clients into quirks mode, and with no charset or viewport for mobile stacking to use.
     */
    #[DataProvider('designProvider')]
    public function testEveryDesignEmitsACompleteDocument(string $name): void
    {
        $wrapper = $this->designs->build($name, $this->branding())->wrapperHtml;

        $this->assertStringStartsWith('<!doctype html>', $wrapper);
        $this->assertStringContainsString('<meta charset="utf-8">', $wrapper);
        $this->assertStringContainsString('name="viewport"', $wrapper);
        $this->assertStringContainsString('name="color-scheme"', $wrapper);
        $this->assertStringContainsString('</head>', $wrapper, 'HtmlRenderer puts the media query before </head>');
        $this->assertStringContainsString(TemplateModel::BODY_PLACEHOLDER, $wrapper);
    }
}

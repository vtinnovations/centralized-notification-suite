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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Block;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Block\AbstractBlock;
use VTInnovations\CentralizedNotificationSuite\Message\Branding;
use VTInnovations\CentralizedNotificationSuite\Tests\Block\Fixtures\BlockFactory;

class BlockMarkupTest extends TestCase
{
    /**
     * A row with every shared and type-specific field populated, so one provider covers all
     * types without each needing its own fixture.
     */
    private function row(string $type): array
    {
        return [
            'type' => $type,
            'heading' => 'Heading text',
            'heading_level' => 'h2',
            'body_text' => "First line\nsame paragraph\n\nSecond paragraph",
            'text_style' => 'normal',
            'list_style' => 'bullet',
            'link_text' => 'Press me',
            'link_url' => 'https://example.com/a?b=1&c=2',
            'button_style' => 'solid',
            'image' => 'uuid-placeholder',
            'image_alt' => 'Alt text',
            'image_width' => 400,
            'teaser_layout' => 'image_left',
            'details_rows' => serialize([['key' => 'Label', 'value' => "Value\nsecond"]]),
            'token_name' => 'all_fields_html',
            'custom_html' => '<p>custom</p>',
            'align' => 'center',
            'space_after' => 20,
            'published' => '1',
        ];
    }

    public static function typeProvider(): array
    {
        return array_map(static fn (string $n): array => [$n], BlockFactory::registry()->getNames());
    }

    private function branding(): Branding
    {
        return new Branding(logoUrl: '/files/logo.png', brandColor: '#c8102e', companyName: 'VT');
    }

    /**
     * Conditional comments open and close in separate comments (the Outlook ghost table), so
     * they have to come out before any tag can be balance-counted.
     */
    private function withoutConditionals(string $html): string
    {
        return (string) preg_replace('/<!--\[if.*?<!\[endif\]-->/s', '', $html);
    }

    #[DataProvider('typeProvider')]
    public function testEveryTypeProducesBalancedMarkup(string $type): void
    {
        $html = BlockFactory::registry()->get($type)->render($this->row($type), $this->branding());

        $this->assertNotSame('', trim($html), 'a fully populated row must render something');

        $stripped = $this->withoutConditionals($html);

        foreach (['table', 'tr', 'td'] as $tag) {
            $this->assertSame(
                substr_count($stripped, '<'.$tag),
                substr_count($stripped, '</'.$tag.'>'),
                \sprintf('<%s> must be balanced in the %s block', $tag, $type),
            );
        }
    }

    #[DataProvider('typeProvider')]
    public function testEveryTypeIsSafeToEmbedInAnEmail(string $type): void
    {
        $html = BlockFactory::registry()->get($type)->render($this->row($type), $this->branding());

        $this->assertStringNotContainsStringIgnoringCase('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $html, 'no event handler attributes');
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
        // The card is 600px wide with 32px of padding, so 536 is all a block ever gets
        $this->assertStringNotContainsString('width="600"', $html);
        $this->assertStringNotContainsString('&amp;lt;', $html, 'double-escaping canary');
    }

    #[DataProvider('typeProvider')]
    public function testEveryTypeRendersNothingForAnEmptyRow(string $type): void
    {
        // divider is the one block that is pure chrome and has nothing to be empty of
        if ('divider' === $type) {
            $this->markTestSkipped('the divider has no content of its own');
        }

        $this->assertSame('', trim(BlockFactory::registry()->get($type)->render(['type' => $type], $this->branding())));
    }

    public function testStoredValuesAreEscapedButTokensSurvive(): void
    {
        $row = $this->row('heading');
        $row['heading'] = '<script>alert(1)</script> Tom & Jerry ##name##';

        $html = BlockFactory::registry()->get('heading')->render($row, $this->branding());

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('Tom &amp; Jerry', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html, 'escaped exactly once');
        $this->assertStringContainsString('##name##', $html, 'tokens are resolved later, not here');
    }

    public function testAJavascriptUrlIsNeutralised(): void
    {
        $row = $this->row('button');
        $row['link_url'] = 'javascript:alert(1)';

        $html = BlockFactory::registry()->get('button')->render($row, $this->branding());

        $this->assertStringContainsString('href="#"', $html);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
    }

    public function testInsertTagsAndTokensPassThroughAUrlUnmangled(): void
    {
        foreach (['{{link_url::5}}', '##link##'] as $url) {
            $row = $this->row('button');
            $row['link_url'] = $url;

            $this->assertStringContainsString(
                $url,
                BlockFactory::registry()->get('button')->render($row, $this->branding()),
            );
        }
    }

    public function testImageWidthIsClampedToTheUsableContentWidth(): void
    {
        $row = $this->row('image');
        $row['image_width'] = 9999;

        $html = BlockFactory::registry()->get('image')->render($row, $this->branding());

        $this->assertStringContainsString('width="'.AbstractBlock::CONTENT_WIDTH.'"', $html);
    }

    public function testTheButtonLabelContrastsWithTheBrandColour(): void
    {
        $row = $this->row('button');

        $onDark = BlockFactory::registry()->get('button')->render($row, new Branding(brandColor: '#0b5fff'));
        $onPale = BlockFactory::registry()->get('button')->render($row, new Branding(brandColor: '#ffe066'));

        $this->assertStringContainsString('color:#ffffff', $onDark);
        $this->assertStringContainsString('color:#11181c', $onPale);
    }

    /**
     * A style block would be hoisted out of the body by HtmlRenderer and inlined against the
     * whole document, so one block's rule would restyle the entire message.
     */
    public function testTheCustomHtmlBlockStripsStyleBlocks(): void
    {
        $row = $this->row('html');
        $row['custom_html'] = '<p>keep</p><style>p{color:red}</style>';

        $html = BlockFactory::registry()->get('html')->render($row, $this->branding());

        $this->assertStringContainsString('<p>keep</p>', $html);
        $this->assertStringNotContainsStringIgnoringCase('<style', $html);
    }

    public function testTheDetailTableIsARealTableNotAPresentationalOne(): void
    {
        $html = BlockFactory::registry()->get('details')->render($this->row('details'), $this->branding());

        // The row() wrapper is presentational; the data table inside it must not be
        preg_match_all('/<table[^>]*>/', $html, $matches);
        $this->assertCount(2, $matches[0]);
        $this->assertStringContainsString('presentation', $matches[0][0]);
        $this->assertStringNotContainsString('presentation', $matches[0][1]);
        $this->assertStringContainsString('scope="row"', $html);
        // Outlook ignores border-collapse and uses cellspacing instead
        $this->assertStringContainsString('cellspacing="0"', $matches[0][1]);
    }

    public function testTheTeaserStacksWithoutAMediaQueryWhenItHasNoImage(): void
    {
        $row = $this->row('teaser');
        $row['image'] = null;

        $html = BlockFactory::registry()->get('teaser')->render($row, $this->branding());

        $this->assertNotSame('', trim($html));
        $this->assertStringNotContainsString('sn-blk-cols', $html, 'nothing to sit beside means a single column');
    }

    public function testTheTeaserKeepsItsColumnsForOutlookAndCanReclaimThemOnMobile(): void
    {
        $html = BlockFactory::registry()->get('teaser')->render($this->row('teaser'), $this->branding());

        // The ghost table is what stops Outlook stacking the columns permanently
        $this->assertStringContainsString('<!--[if mso]>', $html);
        // font-size:0 removes the whitespace node the inliner introduces between the columns
        $this->assertStringContainsString('font-size:0', $html);
        // These two classes are the only hook the media query has
        $this->assertStringContainsString('sn-blk-cols', $html);
        $this->assertStringContainsString('sn-blk-col', $html);
    }
}

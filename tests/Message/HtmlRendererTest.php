<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Tests\Message;

use PHPUnit\Framework\TestCase;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use VTInnovations\SimpleNotifyBundle\Message\EmailLayout;
use VTInnovations\SimpleNotifyBundle\Message\HtmlRenderer;

class HtmlRendererTest extends TestCase
{
    private HtmlRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new HtmlRenderer(new CssToInlineStyles());
    }

    public function testReturnsBodyUnchangedWithoutALayout(): void
    {
        $this->assertSame('<p>Hi</p>', $this->renderer->render('<p>Hi</p>', null, false));
    }

    public function testWrapsBodyInHeaderAndFooter(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'headerHtml' => '<div>Head</div>',
            'footerHtml' => '<div>Foot</div>',
        ]), false);

        $this->assertSame('<div>Head</div><p>Body</p><div>Foot</div>', $result);
    }

    public function testSubstitutesTheBodyPlaceholder(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'mode' => EmailLayout::MODE_WRAPPER,
            'wrapperHtml' => '<main>##message_body##</main>',
        ]), false);

        $this->assertSame('<main><p>Body</p></main>', $result);
    }

    /**
     * Losing the message entirely because someone forgot the placeholder would be the
     * worst possible outcome, so the body is appended instead.
     */
    public function testKeepsTheBodyWhenTheWrapperHasNoPlaceholder(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'mode' => EmailLayout::MODE_WRAPPER,
            'wrapperHtml' => '<main>frame</main>',
        ]), false);

        $this->assertStringContainsString('<p>Body</p>', $result);
    }

    public function testFallsBackToTheBodyWhenTheWrapperIsEmpty(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'mode' => EmailLayout::MODE_WRAPPER,
            'wrapperHtml' => '   ',
        ]), false);

        $this->assertSame('<p>Body</p>', $result);
    }

    public function testInlinesCssOntoElements(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'css' => 'p { color: #ff0000; }',
        ]));

        $this->assertMatchesRegularExpression('/<p[^>]*style="[^"]*#ff0000/i', $result);
    }

    /**
     * Media queries cannot be expressed inline; dropping them would silently remove the
     * responsive behaviour of a pasted design.
     */
    public function testKeepsMediaQueriesAsAStyleBlock(): void
    {
        $css = 'p { color: #ff0000; } @media (max-width: 600px) { p { font-size: 12px; } }';
        $result = $this->renderer->render('<p>Body</p>', $this->layout(['css' => $css]));

        $this->assertStringContainsString('@media', $result);
        $this->assertStringContainsString('font-size: 12px', $result);
        $this->assertMatchesRegularExpression('/<p[^>]*style="[^"]*#ff0000/i', $result);
    }

    public function testInlinesStyleBlocksFoundInTheMarkupItself(): void
    {
        $wrapper = '<html><head><style>p { color: #00ff00; }</style></head><body>##message_body##</body></html>';
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'mode' => EmailLayout::MODE_WRAPPER,
            'wrapperHtml' => $wrapper,
        ]));

        $this->assertMatchesRegularExpression('/<p[^>]*style="[^"]*#00ff00/i', $result);
    }

    public function testLayoutCanDisableInlining(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'css' => 'p { color: #ff0000; }',
            'inlineCss' => false,
        ]));

        $this->assertStringContainsString('<style', $result);
        $this->assertDoesNotMatchRegularExpression('/<p[^>]*style=/i', $result);
    }

    public function testPreheaderIsAddedHiddenAndEscaped(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout([
            'preheader' => 'Preview & more',
        ]), false);

        $this->assertStringContainsString('display:none', $result);
        $this->assertStringContainsString('Preview &amp; more', $result);
        $this->assertStringStartsWith('<div style="display:none', $result);
    }

    public function testNoPreheaderMarkupWhenNoneIsSet(): void
    {
        $result = $this->renderer->render('<p>Body</p>', $this->layout(['preheader' => '  ']), false);

        $this->assertSame('<p>Body</p>', $result);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function layout(array $overrides = []): EmailLayout
    {
        return new EmailLayout(...[...[
            'mode' => EmailLayout::MODE_HEADER_FOOTER,
            'wrapperHtml' => '',
            'headerHtml' => '',
            'footerHtml' => '',
            'css' => '',
            'preheader' => '',
            'inlineCss' => true,
        ], ...$overrides]);
    }
}

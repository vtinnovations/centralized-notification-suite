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
use VTInnovations\CentralizedNotificationSuite\Message\PlainTextConverter;

class PlainTextConverterTest extends TestCase
{
    private PlainTextConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new PlainTextConverter();
    }

    public function testReturnsEmptyStringForBlankInput(): void
    {
        $this->assertSame('', $this->converter->convert(''));
        $this->assertSame('', $this->converter->convert("  \n "));
    }

    public function testKeepsLinkTargets(): void
    {
        $this->assertSame(
            'Confirm your address (https://example.com/c)',
            $this->converter->convert('<p><a href="https://example.com/c">Confirm your address</a></p>'),
        );
    }

    public function testDoesNotRepeatALinkWhoseLabelIsItsUrl(): void
    {
        $this->assertSame(
            'https://example.com',
            $this->converter->convert('<a href="https://example.com">https://example.com</a>'),
        );
    }

    public function testDoesNotRepeatAMailtoLinkLabel(): void
    {
        $this->assertSame(
            'jane@example.com',
            $this->converter->convert('<a href="mailto:jane@example.com">jane@example.com</a>'),
        );
    }

    public function testDropsTheHiddenPreheader(): void
    {
        $html = '<div style="display:none;max-height:0">Inbox preview text</div><p>Real content</p>';

        $this->assertSame('Real content', $this->converter->convert($html));
    }

    public function testDropsScriptAndStyleContents(): void
    {
        $html = '<style>p { color: red }</style><script>alert(1)</script><p>Body</p>';

        $this->assertSame('Body', $this->converter->convert($html));
    }

    public function testTurnsListItemsIntoDashes(): void
    {
        $this->assertSame(
            "- One\n\n- Two",
            $this->converter->convert('<ul><li>One</li><li>Two</li></ul>'),
        );
    }

    public function testDecodesEntities(): void
    {
        $this->assertSame('Smith & Sons — "quoted"', $this->converter->convert('<p>Smith &amp; Sons &mdash; &quot;quoted&quot;</p>'));
    }

    #[DataProvider('whitespaceProvider')]
    public function testNormalisesWhitespace(string $html, string $expected): void
    {
        $this->assertSame($expected, $this->converter->convert($html));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function whitespaceProvider(): iterable
    {
        yield 'collapses runs of blank lines' => ['<p>A</p><p></p><p></p><p>B</p>', "A\n\nB"];
        yield 'collapses repeated spaces' => ['<p>A     B</p>', 'A B'];
        yield 'br becomes a single newline' => ['<p>A<br>B</p>', "A\nB"];
        yield 'non-breaking space becomes a space' => ['<p>A&nbsp;B</p>', 'A B'];
        yield 'indentation is stripped' => ["<p>\n    A\n</p>", 'A'];
    }
}

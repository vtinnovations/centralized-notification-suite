<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Tests\Message;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Part\DataPart;
use VTInnovations\SimpleNotifyBundle\Message\ImageEmbedder;

class ImageEmbedderTest extends TestCase
{
    private string $projectDir;

    private ImageEmbedder $embedder;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/sn-embed-'.bin2hex(random_bytes(6));
        mkdir($this->projectDir.'/files', 0777, true);

        // A 1x1 PNG, so mime_content_type() reports image/png
        file_put_contents(
            $this->projectDir.'/files/logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true),
        );

        $stack = new RequestStack();
        $stack->push(Request::create('http://example.com/'));

        $this->embedder = new ImageEmbedder($this->projectDir, $stack);
    }

    protected function tearDown(): void
    {
        @unlink($this->projectDir.'/files/logo.png');
        @rmdir($this->projectDir.'/files');
        @rmdir($this->projectDir);
    }

    /**
     * Symfony's DataPart::setContentId() rejects a content ID without an "@", and
     * Email::prepareParts() pairs a "cid:" reference with its part by comparing the
     * reference against the content ID verbatim. A bare hash therefore threw at send time
     * and no mail went out at all.
     */
    public function testContentIdIsAcceptedBySymfonyAndMatchesTheSrc(): void
    {
        [$html, $attachments] = $this->embedder->embed('<img src="/files/logo.png">');

        $this->assertCount(1, $attachments);
        $cid = (string) $attachments[0]->cid;

        $this->assertStringContainsString('@', $cid, 'A content ID without "@" is rejected by Symfony.');
        $this->assertStringContainsString('src="cid:'.$cid.'"', $html, 'The src must reference the content ID verbatim.');

        // Would throw InvalidArgumentException before the fix
        $part = new DataPart('bytes', $attachments[0]->name, $attachments[0]->type);
        $part->setContentId($cid);

        $this->assertSame($cid, $part->getContentId());
    }

    public function testEmbedsRelativeAndSameHostSources(): void
    {
        foreach (['/files/logo.png', 'files/logo.png', 'http://example.com/files/logo.png'] as $src) {
            [$html, $attachments] = $this->embedder->embed('<img src="'.$src.'">');

            $this->assertCount(1, $attachments, $src.' should be embedded');
            $this->assertStringStartsWith('cid:', $this->srcOf($html));
        }
    }

    public function testLeavesForeignAndNonFileSourcesAlone(): void
    {
        foreach ([
            'https://cdn.example.org/logo.png',   // different host
            'cid:already-inline@x',
            'data:image/png;base64,AAA',
            '/files/missing.png',
            '/files/notanimage.txt',
        ] as $src) {
            [$html, $attachments] = $this->embedder->embed('<img src="'.$src.'">');

            $this->assertSame([], $attachments, $src.' must not be embedded');
            $this->assertSame($src, $this->srcOf($html));
        }
    }

    /**
     * A "../" in pasted markup must not be able to attach a file from outside the project.
     */
    public function testRejectsTraversalOutsideTheProject(): void
    {
        [, $attachments] = $this->embedder->embed('<img src="/files/../../../../etc/hostname.png">');

        $this->assertSame([], $attachments);
    }

    public function testAttachesAFileUsedTwiceOnlyOnce(): void
    {
        [$html, $attachments] = $this->embedder->embed('<img src="/files/logo.png"><img src="files/logo.png">');

        $this->assertCount(1, $attachments);
        $this->assertSame(2, preg_match_all('/cid:/', $html));
    }

    public function testIgnoresQueryStringsWhenResolving(): void
    {
        [, $attachments] = $this->embedder->embed('<img src="/files/logo.png?v=2">');

        $this->assertCount(1, $attachments);
        $this->assertSame('logo.png', $attachments[0]->name);
    }

    public function testKeepsMarkupUntouchedWithoutImages(): void
    {
        [$html, $attachments] = $this->embedder->embed('<p>No images here</p>');

        $this->assertSame('<p>No images here</p>', $html);
        $this->assertSame([], $attachments);
    }

    public function testMarksTheAttachmentAsInline(): void
    {
        [, $attachments] = $this->embedder->embed('<img src="/files/logo.png">');

        $this->assertTrue($attachments[0]->isInline());
        $this->assertSame('image/png', $attachments[0]->type);
    }

    private function srcOf(string $html): string
    {
        preg_match('/src="([^"]*)"/', $html, $m);

        return $m[1] ?? '';
    }
}

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

use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Block\BlockBodyRenderer;
use VTInnovations\CentralizedNotificationSuite\Message\Branding;
use VTInnovations\CentralizedNotificationSuite\Tests\Block\Fixtures\BlockFactory;

class BlockBodyRendererTest extends TestCase
{
    private BlockBodyRenderer $renderer;

    protected function setUp(): void
    {
        // compose() never touches the database, so the framework is only here to satisfy the
        // constructor -- which is the point of keeping the loading and the composing apart.
        $this->renderer = new BlockBodyRenderer(BlockFactory::registry(), $this->createMock(ContaoFramework::class));
    }

    private function branding(): Branding
    {
        return new Branding(brandColor: '#c8102e');
    }

    public function testNoBlocksProducesNothing(): void
    {
        $this->assertSame('', $this->renderer->compose([], $this->branding()));
    }

    /**
     * An empty body makes MessageRenderer send the text part alone, which is the pre-existing
     * behaviour for a message with an empty html field.
     */
    public function testBlocksThatRenderNothingProduceNothing(): void
    {
        $this->assertSame('', $this->renderer->compose([['type' => 'heading', 'heading' => '']], $this->branding()));
    }

    public function testKeepsTheGivenOrder(): void
    {
        $body = $this->renderer->compose([
            ['type' => 'heading', 'heading' => 'FIRST'],
            ['type' => 'heading', 'heading' => 'SECOND'],
        ], $this->branding());

        $this->assertLessThan(strpos($body, 'SECOND'), strpos($body, 'FIRST'));
    }

    /**
     * A third-party block that is no longer installed must not stop a notification going out.
     */
    public function testAnUnknownTypeIsSkippedAndItsNeighboursStillRender(): void
    {
        $body = $this->renderer->compose([
            ['type' => 'heading', 'heading' => 'BEFORE'],
            ['type' => 'not-installed', 'heading' => 'GONE'],
            ['type' => 'heading', 'heading' => 'AFTER'],
        ], $this->branding());

        $this->assertStringContainsString('BEFORE', $body);
        $this->assertStringContainsString('AFTER', $body);
        $this->assertStringNotContainsString('GONE', $body);
    }

    /**
     * The design's own .sn-body padding already closes the card, so a trailing gap would
     * double it.
     */
    public function testTheLastVisibleBlockHasNoSpaceBelowIt(): void
    {
        $body = $this->renderer->compose([
            ['type' => 'heading', 'heading' => 'A', 'space_after' => 24],
            ['type' => 'heading', 'heading' => 'B', 'space_after' => 24],
        ], $this->branding());

        $this->assertSame(1, substr_count($body, 'padding:0 0 24px'));
        $this->assertSame(1, substr_count($body, 'padding:0 0 0px'));
    }

    public function testSpacingIsClampedToSomethingSane(): void
    {
        foreach ([['abc', 0], [-5, 0], [9999, 80]] as [$given, $expected]) {
            $body = $this->renderer->compose([
                ['type' => 'heading', 'heading' => 'A', 'space_after' => $given],
                ['type' => 'heading', 'heading' => 'B'],
            ], $this->branding());

            $this->assertStringContainsString('padding:0 0 '.$expected.'px', $body);
        }
    }

    public function testASingleBlockStillGetsItsWrapper(): void
    {
        $body = $this->renderer->compose([['type' => 'heading', 'heading' => 'Solo']], $this->branding());

        $this->assertStringContainsString('Solo', $body);
        $this->assertSame(substr_count($body, '<table'), substr_count($body, '</table>'));
    }

    public function testRenderOneIgnoresAnUnknownType(): void
    {
        $this->assertSame('', $this->renderer->renderOne(['type' => 'nope'], $this->branding()));
        $this->assertNotSame('', $this->renderer->renderOne(['type' => 'heading', 'heading' => 'x'], $this->branding()));
    }
}

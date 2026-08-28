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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Block\Fixtures;

use VTInnovations\CentralizedNotificationSuite\Block\BlockRegistry;
use VTInnovations\CentralizedNotificationSuite\Block\ButtonBlock;
use VTInnovations\CentralizedNotificationSuite\Block\DetailsBlock;
use VTInnovations\CentralizedNotificationSuite\Block\DividerBlock;
use VTInnovations\CentralizedNotificationSuite\Block\FileUrlResolver;
use VTInnovations\CentralizedNotificationSuite\Block\HeadingBlock;
use VTInnovations\CentralizedNotificationSuite\Block\HtmlBlock;
use VTInnovations\CentralizedNotificationSuite\Block\ImageBlock;
use VTInnovations\CentralizedNotificationSuite\Block\ListBlock;
use VTInnovations\CentralizedNotificationSuite\Block\ParagraphBlock;
use VTInnovations\CentralizedNotificationSuite\Block\TeaserBlock;
use VTInnovations\CentralizedNotificationSuite\Block\TokenBlock;

/**
 * Builds the real block set without the container, so block behaviour can be asserted
 * without booting Contao. The file resolver is stubbed because resolving a UUID needs the
 * database.
 */
final class BlockFactory
{
    public static function registry(): BlockRegistry
    {
        return new BlockRegistry(self::blocks());
    }

    /**
     * @return list<\VTInnovations\CentralizedNotificationSuite\Block\BlockInterface>
     */
    public static function blocks(): array
    {
        $files = new class extends FileUrlResolver {
            public function resolve(mixed $uuid): string
            {
                return $uuid ? '/files/example.png' : '';
            }
        };

        return [
            new HeadingBlock(),
            new ParagraphBlock(),
            new ListBlock(),
            new ButtonBlock(),
            new DividerBlock(),
            new DetailsBlock(),
            new TokenBlock(),
            new HtmlBlock(),
            new ImageBlock($files),
            new TeaserBlock($files),
        ];
    }
}

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

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Block\BlockRegistry;
use VTInnovations\CentralizedNotificationSuite\Exception\NotificationException;
use VTInnovations\CentralizedNotificationSuite\Tests\Block\Fixtures\BlockFactory;

class BlockRegistryTest extends TestCase
{
    public function testIndexesByNameAndSortsThem(): void
    {
        $registry = BlockFactory::registry();

        $this->assertSame(array_keys($registry->all()), $registry->getNames());
        $sorted = $registry->getNames();
        sort($sorted);
        $this->assertSame($sorted, $registry->getNames(), 'names are sorted');
    }

    public function testKnowsWhichTypesExist(): void
    {
        $registry = BlockFactory::registry();

        $this->assertTrue($registry->has('heading'));
        $this->assertFalse($registry->has('nope'));
        $this->assertSame('heading', $registry->get('heading')->getName());
    }

    public function testAnUnknownTypeThrows(): void
    {
        $this->expectException(NotificationException::class);
        $this->expectExceptionMessageMatches('/nope/');

        BlockFactory::registry()->get('nope');
    }

    public function testEveryTypeAppearsExactlyOnceAcrossGroups(): void
    {
        $registry = BlockFactory::registry();
        $grouped = $registry->getGroupedOptions();
        $flat = array_merge(...array_map('array_keys', array_values($grouped)));

        sort($flat);
        $expected = $registry->getNames();
        sort($expected);

        $this->assertSame($expected, $flat);
        $this->assertSame($flat, array_unique($flat));
    }
}

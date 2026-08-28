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

namespace VTInnovations\CentralizedNotificationSuite\Block;

use VTInnovations\CentralizedNotificationSuite\Exception\NotificationException;

class BlockRegistry
{
    /** @var array<string, BlockInterface> */
    private array $blocks = [];

    /**
     * @param iterable<BlockInterface> $blocks
     */
    public function __construct(iterable $blocks)
    {
        foreach ($blocks as $block) {
            $this->blocks[$block->getName()] = $block;
        }

        ksort($this->blocks);
    }

    public function has(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    public function get(string $name): BlockInterface
    {
        return $this->blocks[$name] ?? throw NotificationException::unknownBlock($name);
    }

    /**
     * @return array<string, BlockInterface>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->blocks);
    }

    /**
     * Group heading => [type name => label], for the grouped type select. Labels come from
     * the language file so a third-party block can be translated without touching this class.
     *
     * @return array<string, array<string, string>>
     */
    public function getGroupedOptions(): array
    {
        $labels = $GLOBALS['TL_LANG']['tl_notification_block']['type_options'] ?? [];
        $grouped = [];

        foreach ($this->blocks as $name => $block) {
            $grouped[$block->getGroup()][$name] = (string) ($labels[$name] ?? $name);
        }

        return $grouped;
    }
}

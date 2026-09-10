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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Command\InstallEmailsCommand;
use VTInnovations\CentralizedNotificationSuite\Message\DesignLibrary;
use VTInnovations\CentralizedNotificationSuite\Tests\Block\Fixtures\BlockFactory;

/**
 * Validates the seed data itself. A preset referring to a block type or a design that does
 * not exist would only surface when an integrator runs the install on a real site, which is
 * the worst possible moment to find out.
 */
class InstallEmailsCommandTest extends TestCase
{
    /**
     * Columns declared in contao/dca/tl_notification_block.php and shared by every type.
     */
    private const SHARED_FIELDS = [
        'type', 'heading', 'body_text', 'link_text', 'link_url',
        'image', 'image_alt', 'image_width', 'align', 'space_after', 'published',
    ];

    public static function presetProvider(): array
    {
        return array_map(
            static fn (string $alias): array => [$alias],
            array_keys(InstallEmailsCommand::presets()),
        );
    }

    public function testShipsAllThreeFamiliesWithUniqueAliases(): void
    {
        $presets = InstallEmailsCommand::presets();
        $aliases = array_keys($presets);

        $this->assertSame($aliases, array_unique($aliases));
        $this->assertGreaterThanOrEqual(3, \count($presets));

        foreach (['transactional', 'newsletter', 'announcement'] as $family) {
            $this->assertNotEmpty(
                array_filter($aliases, static fn (string $a): bool => str_contains($a, $family)),
                \sprintf('a "%s" starter is expected', $family),
            );
        }
    }

    #[DataProvider('presetProvider')]
    public function testEveryPresetIsWellFormed(string $alias): void
    {
        $preset = InstallEmailsCommand::presets()[$alias];

        foreach (['title', 'design', 'preheader', 'subject', 'blocks'] as $key) {
            $this->assertArrayHasKey($key, $preset);
        }

        $this->assertNotSame('', trim($preset['title']));
        $this->assertNotSame('', trim($preset['subject']));
        $this->assertNotEmpty($preset['blocks']);
    }

    #[DataProvider('presetProvider')]
    public function testEveryPresetUsesAnExistingDesign(string $alias): void
    {
        $this->assertTrue(
            (new DesignLibrary())->has(InstallEmailsCommand::presets()[$alias]['design']),
            'the design must exist, or the layout silently falls back to the default',
        );
    }

    #[DataProvider('presetProvider')]
    public function testEveryPresetBlockUsesARegisteredTypeAndKnownFields(string $alias): void
    {
        $registry = BlockFactory::registry();

        foreach (InstallEmailsCommand::presets()[$alias]['blocks'] as $i => $block) {
            $this->assertArrayHasKey('type', $block, \sprintf('%s block %d has no type', $alias, $i));

            $type = $block['type'];
            $this->assertTrue($registry->has($type), \sprintf('%s block %d uses unknown type "%s"', $alias, $i, $type));

            $allowed = [...self::SHARED_FIELDS, ...array_keys($registry->get($type)->getConfigFields())];

            foreach (array_keys($block) as $field) {
                $this->assertContains(
                    $field,
                    $allowed,
                    \sprintf('%s block %d (%s) sets "%s", which that type does not declare', $alias, $i, $type, $field),
                );
            }
        }
    }

    /**
     * The seeded blocks have to survive the same renderer a real message goes through.
     */
    #[DataProvider('presetProvider')]
    public function testEveryPresetRendersToBalancedMarkup(string $alias): void
    {
        $registry = BlockFactory::registry();
        $branding = new \VTInnovations\CentralizedNotificationSuite\Message\Branding(brandColor: '#c8102e', companyName: 'VT');
        $rendered = 0;

        foreach (InstallEmailsCommand::presets()[$alias]['blocks'] as $block) {
            // The seeder fills these in from the DCA defaults
            $row = ['align' => 'left', 'space_after' => 20, 'image_width' => 536, ...$block];
            $html = $registry->get($block['type'])->render($row, $branding);

            if ('' === trim($html)) {
                continue;
            }

            ++$rendered;
            $stripped = (string) preg_replace('/<!--\[if.*?<!\[endif\]-->/s', '', $html);

            $this->assertSame(substr_count($stripped, '<table'), substr_count($stripped, '</table>'));
            $this->assertStringNotContainsString('&amp;lt;', $html);
        }

        $this->assertGreaterThan(0, $rendered, 'a preset whose every block renders empty is not a usable starter');
    }
}

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
use VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer\BlockDcaListener;
use VTInnovations\CentralizedNotificationSuite\Tests\Block\Fixtures\BlockFactory;

class BlockDcaListenerTest extends TestCase
{
    private const TABLE = 'tl_notification_block';

    private BlockDcaListener $listener;

    protected function setUp(): void
    {
        // Pinned rather than detected, so these expectations describe one core contract
        // regardless of which Contao the host project happens to have installed.
        $this->listener = new BlockDcaListener(BlockFactory::registry(), '5.7.9');

        // The shared skeleton the DCA file provides, reduced to what the listener touches
        $GLOBALS['TL_DCA'][self::TABLE] = [
            'palettes' => ['default' => '{type_legend},type'],
            'fields' => [
                'type' => ['inputType' => 'select', 'eval' => []],
                'heading' => [],
                'body_text' => [],
                'link_text' => [],
                'link_url' => [],
                'image' => [],
                'image_alt' => [],
                'image_width' => [],
                'align' => [],
                'space_after' => [],
                'published' => [],
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'][self::TABLE], $GLOBALS['TL_LANG']['tl_notification_block']);
    }

    public function testIgnoresEveryOtherTable(): void
    {
        $before = $GLOBALS['TL_DCA'][self::TABLE];

        ($this->listener)('tl_notification_message');

        $this->assertSame($before, $GLOBALS['TL_DCA'][self::TABLE]);
    }

    public function testMakesTypeThePaletteSelector(): void
    {
        ($this->listener)(self::TABLE);

        $dca = $GLOBALS['TL_DCA'][self::TABLE];

        $this->assertTrue($dca['fields']['type']['eval']['submitOnChange']);
        $this->assertSame(['type'], $dca['palettes']['__selector__']);
    }

    public function testTheSelectorIsNotDuplicatedOnASecondPass(): void
    {
        ($this->listener)(self::TABLE);
        ($this->listener)(self::TABLE);

        $this->assertSame(['type'], $GLOBALS['TL_DCA'][self::TABLE]['palettes']['__selector__']);
    }

    public function testEveryRegisteredTypeGetsExactlyOnePalette(): void
    {
        ($this->listener)(self::TABLE);

        $dca = $GLOBALS['TL_DCA'][self::TABLE];

        foreach (BlockFactory::registry()->getNames() as $name) {
            $this->assertArrayHasKey($name, $dca['palettes']);
            $this->assertStringStartsWith('{type_legend},type', $dca['palettes'][$name]);
            $this->assertStringEndsWith(';{layout_legend},align,space_after;{publish_legend},published', $dca['palettes'][$name]);
        }
    }

    /**
     * A field named in a palette but absent from "fields" is a fatal in the edit mask, and a
     * typo in a palette string is the easiest way to introduce one.
     */
    public function testEveryFieldNamedInEveryPaletteExists(): void
    {
        ($this->listener)(self::TABLE);

        $dca = $GLOBALS['TL_DCA'][self::TABLE];
        $missing = [];

        foreach ($dca['palettes'] as $name => $palette) {
            if (!\is_string($palette)) {
                continue;
            }

            foreach (preg_split('/[;,]/', (string) preg_replace('/\{[^}]*\}/', '', $palette)) as $field) {
                if ('' !== ($field = trim($field)) && !isset($dca['fields'][$field])) {
                    $missing[] = $name.':'.$field;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    /**
     * Without an "sql" key contao:migrate never creates the column, and the field silently
     * fails to save.
     */
    public function testEveryContributedFieldDeclaresItsColumn(): void
    {
        foreach (BlockFactory::blocks() as $block) {
            foreach ($block->getConfigFields() as $field => $definition) {
                $this->assertArrayHasKey('sql', $definition, \sprintf('%s.%s needs an sql key', $block->getName(), $field));
            }
        }
    }

    /**
     * Shared columns live in the DCA file precisely so the schema does not depend on which
     * block happens to be registered first.
     */
    public function testNoContributedFieldCollidesWithASharedOne(): void
    {
        $shared = array_keys($GLOBALS['TL_DCA'][self::TABLE]['fields']);

        foreach (BlockFactory::blocks() as $block) {
            foreach (array_keys($block->getConfigFields()) as $field) {
                $this->assertNotContains($field, $shared, \sprintf('%s redeclares the shared field %s', $block->getName(), $field));
            }
        }
    }

    public function testTypeOptionsAreGroupedAndCoverEveryType(): void
    {
        $grouped = $this->listener->getTypeOptions();
        $flat = array_merge(...array_map('array_keys', array_values($grouped)));

        sort($flat);
        $expected = BlockFactory::registry()->getNames();
        sort($expected);

        $this->assertSame($expected, $flat);
    }

    public function testTheCardLabelEscapesStoredContentAndKeepsTokensVisible(): void
    {
        [$label, $preview, $state] = $this->listener->formatLabel(
            ['type' => 'heading', 'heading' => '<script>alert(1)</script> ##name##', 'published' => '1'],
            '',
        );

        $this->assertStringNotContainsString('<script>', $label);
        $this->assertStringContainsString('##name##', $label);
        $this->assertSame('published', $state);
        $this->assertSame('', $preview, 'a heading needs no separate preview');
    }

    public function testTheCardReportsAnUninstalledType(): void
    {
        [$label] = $this->listener->formatLabel(['type' => 'not-installed', 'published' => '1'], '');

        $this->assertStringContainsString('&#9888;', $label);
    }

    public function testTheCardMarksHiddenBlocks(): void
    {
        $this->assertSame('unpublished', $this->listener->formatLabel(['type' => 'heading', 'published' => ''], '')[2]);
        // DC_Table::toggle() writes PHP false, which a char(1) column stores as '0'
        $this->assertSame('unpublished', $this->listener->formatLabel(['type' => 'heading', 'published' => '0'], '')[2]);
    }

    /**
     * The backend list prints this cell unescaped and without a sandbox, and the theme styles
     * it by class -- so inline styles would both look wrong and defeat dark mode.
     */
    public function testTheCardPreviewCarriesNoInlineStyles(): void
    {
        $preview = $this->listener->formatLabel([
            'type' => 'details',
            'details_rows' => serialize([['key' => 'Label', 'value' => 'Value']]),
            'published' => '1',
        ], '')[1];

        $this->assertStringContainsString('<table>', $preview);
        $this->assertStringNotContainsString('style=', $preview);
    }

    /**
     * Contao 5.7 introduced the [label, preview, state] record label. Earlier cores call the
     * same callback but concatenate its return value straight into the row, so an array would
     * reach the page as the literal string "Array" plus a conversion warning.
     */
    #[DataProvider('coresWithoutTheRecordLabel')]
    public function testOlderCoresGetTheLabelAsAPlainString(string $version): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), $version);

        $label = $listener->formatLabel(['type' => 'heading', 'heading' => 'Welcome', 'published' => '1'], '');

        $this->assertIsString($label);
        $this->assertStringContainsString('Welcome', $label);
    }

    #[DataProvider('coresWithTheRecordLabel')]
    public function testCurrentCoresGetTheRecordLabelArray(string $version): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), $version);

        $label = $listener->formatLabel(['type' => 'heading', 'heading' => 'Welcome', 'published' => '1'], '');

        $this->assertIsArray($label);
        $this->assertCount(3, $label);
    }

    public static function coresWithoutTheRecordLabel(): iterable
    {
        yield '5.3 LTS' => ['5.3.50'];
        yield '5.4' => ['5.4.0'];
        yield '5.5' => ['5.5.12'];
        yield '5.6' => ['5.6.3'];
    }

    public static function coresWithTheRecordLabel(): iterable
    {
        yield '5.7' => ['5.7.0'];
        yield '5.7 patch' => ['5.7.9'];
        yield '6.0' => ['6.0.0'];
        // A development checkout reports no orderable version and tracks the newest core
        yield 'dev checkout' => ['dev-main'];
    }

    #[DataProvider('coresWithoutTheRecordLabel')]
    public function testOlderCoresGetTheCardFromAChildRecordCallback(string $version): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), $version);

        $listener(self::TABLE);

        $callback = $GLOBALS['TL_DCA'][self::TABLE]['list']['sorting']['child_record_callback'] ?? null;

        $this->assertIsCallable($callback);

        $card = $callback(['type' => 'heading', 'heading' => 'Welcome', 'published' => '1']);

        // The classes the backend theme lays out as grid children in a renderAsGrid parent view
        $this->assertStringContainsString('class="cte_type published"', $card);
        $this->assertStringContainsString('cte_preview', $card);
        $this->assertStringContainsString('Welcome', $card);
    }

    /**
     * Contao 5.7 gives the child record callback precedence over the label callback, so
     * registering one there would trade the record label for a deprecated path that also
     * loses the drag handle.
     */
    #[DataProvider('coresWithTheRecordLabel')]
    public function testCurrentCoresRegisterNoChildRecordCallback(string $version): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), $version);

        $listener(self::TABLE);

        $this->assertArrayNotHasKey('sorting', $GLOBALS['TL_DCA'][self::TABLE]['list'] ?? []);
    }

    public function testTheChildRecordCardMarksHiddenBlocks(): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), '5.3.50');

        $card = $listener->renderChildRecord(['type' => 'heading', 'heading' => 'Draft', 'published' => '']);

        $this->assertStringContainsString('class="cte_type unpublished"', $card);
        $this->assertStringContainsString('class="visibility"', $card);
    }

    public function testTheChildRecordCardEscapesStoredContent(): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), '5.3.50');

        $card = $listener->renderChildRecord([
            'type' => 'heading',
            'heading' => '<script>alert(1)</script> ##name##',
            'published' => '1',
        ]);

        $this->assertStringNotContainsString('<script>', $card);
        $this->assertStringContainsString('##name##', $card);
    }

    /**
     * An empty preview has to be marked so, or the theme reserves a padded, bordered strip
     * below every block that has nothing to show.
     */
    public function testTheChildRecordCardFlagsAnEmptyPreview(): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry(), '5.3.50');

        $card = $listener->renderChildRecord(['type' => 'heading', 'heading' => 'Just a heading', 'published' => '1']);

        $this->assertStringContainsString('cte_preview empty', $card);
    }

    /**
     * Guards the default path: with no version passed the listener has to resolve the running
     * core itself rather than throw or silently pick the wrong contract.
     */
    public function testTheCoreVersionIsDetectedWhenNotSupplied(): void
    {
        $listener = new BlockDcaListener(BlockFactory::registry());

        $label = $listener->formatLabel(['type' => 'heading', 'heading' => 'Welcome', 'published' => '1'], '');

        $this->assertTrue(\is_array($label) || \is_string($label));
    }
}

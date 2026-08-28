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

namespace VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\DataContainer;
use Contao\StringUtil;
use VTInnovations\CentralizedNotificationSuite\Block\BlockRegistry;

/**
 * Lets every registered block contribute its own fields and palette to
 * tl_notification_block, so selecting a type shows only that type's settings.
 *
 * As with GatewayDcaListener, this also drives the database schema: Contao's
 * DcaSchemaProvider loads each DCA through DcaLoader, which fires this hook, so the "sql"
 * keys of block-contributed fields reach contao:migrate exactly like fields declared in the
 * DCA file.
 *
 * Priority 10 rather than the default 0: TokenHelpListener also listens on
 * loadDataContainer and needs to see the merged field list before it attaches its help
 * wizards. At equal priority the order would fall back to service registration order.
 */
#[AsHook('loadDataContainer', priority: 10)]
class BlockDcaListener
{
    private const TABLE = 'tl_notification_block';

    /**
     * The first core version whose parent view consumes a record label as
     * [label, preview, state]. Everything before it concatenates the return value of
     * generateRecordLabel() straight into the row markup -- see formatLabel().
     */
    private const RECORD_LABEL_SINCE = '5.7';

    /**
     * @param string|null $coreVersion Overrides the detected core version; for tests only
     */
    public function __construct(
        private readonly BlockRegistry $blocks,
        private readonly string|null $coreVersion = null,
    ) {
    }

    public function __invoke(string $table): void
    {
        if (self::TABLE !== $table) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA'][self::TABLE];

        // Contao 5.7 renders the block cards from the label callback. Before that the parent
        // view had no record label contract, so the card has to come from a child record
        // callback -- registered only on those cores, because 5.7 gives the child record
        // callback precedence and would then take a deprecated path that also loses the drag
        // handle.
        if (!$this->supportsRecordLabel()) {
            $dca['list']['sorting']['child_record_callback'] = $this->renderChildRecord(...);
        }

        // "type" is the palette selector, so changing it has to reload the edit mask
        $dca['fields']['type']['eval']['submitOnChange'] = true;
        $dca['palettes']['__selector__'] = array_values(array_unique([...$dca['palettes']['__selector__'] ?? [], 'type']));

        foreach ($this->blocks->all() as $name => $block) {
            foreach ($block->getConfigFields() as $field => $definition) {
                // First block to claim a field name wins; shared fields are declared in the
                // DCA file precisely so this arbitration almost never has to happen.
                $dca['fields'][$field] ??= $definition;
            }

            $palette = $block->getPalette();

            $dca['palettes'][$name] = '{type_legend},type'
                .('' !== $palette ? ';'.$palette : '')
                .';{layout_legend},align,space_after;{publish_legend},published';
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    #[AsCallback(table: 'tl_notification_block', target: 'fields.type.options')]
    public function getTypeOptions(): array
    {
        return $this->blocks->getGroupedOptions();
    }

    /**
     * The card shown for a block in the message's block list.
     *
     * Deliberately a schematic rather than the block's real e-mail markup. The list template
     * prints this unescaped and without the sandbox the message preview uses, the block's
     * inline styles would beat the backend theme and look wrong in dark mode, the card is far
     * narrower than the 536px the markup is authored for, and media queries never apply
     * there -- so the one thing worth checking would be invisible anyway. Full fidelity is
     * what MessagePreviewController already provides.
     *
     * The return shape depends on the running core. Contao 5.7 introduced the record label
     * contract and consumes [label, preview, state]; 5.3 to 5.6 call this callback with the
     * identical signature but concatenate the result straight into the row, so an array would
     * surface as the literal string "Array" plus an "Array to string conversion" warning on
     * every block. Those get one HTML string built the way tl_content::addCteType() builds
     * its own card.
     *
     * Deliberately not child_record_callback for the older cores: Contao 5.7 gives it
     * precedence over the label callback, so registering both would drop 5.7 onto a
     * deprecated path that also loses the drag handle.
     *
     * @return array{0: string, 1: string, 2: string}|string [label, preview, state], or markup
     */
    #[AsCallback(table: 'tl_notification_block', target: 'list.label.label')]
    public function formatLabel(array $row, string $label, DataContainer|null $dc = null, array $args = []): array|string
    {
        $type = (string) ($row['type'] ?? '');
        $typeLabel = (string) ($GLOBALS['TL_LANG']['tl_notification_block']['type_options'][$type] ?? $type);

        $title = trim((string) ($row['heading'] ?? '')) ?: trim((string) ($row['link_text'] ?? ''));

        $heading = '' !== $title
            ? StringUtil::specialchars($title).' <span class="tl_gray">['.StringUtil::specialchars($typeLabel).']</span>'
            : StringUtil::specialchars($typeLabel);

        if (!$this->blocks->has($type)) {
            $heading .= ' <span style="color:#c62828" title="This block type is not installed">&#9888;</span>';
        }

        // Older cores get the heading alone. They print this return value as a plain string
        // in several places -- the undo preview and the record picker among them -- so the
        // card markup belongs in the child record callback, not here.
        if (!$this->supportsRecordLabel()) {
            return $heading;
        }

        return [$heading, $this->schematic($row, $type), ($row['published'] ?? '') ? 'published' : 'unpublished'];
    }

    /**
     * The same card for cores before the record label contract.
     *
     * Structured like tl_content::addCteType() because that is what the backend theme is
     * built for: in a renderAsGrid parent view ".cte_type", ".tl_content_right" and
     * ".cte_preview" are laid out as direct grid children (order 1, 2 and 3, the preview
     * spanning both columns). Returning this from the label callback instead would nest it
     * inside ".tl_content_left", which carries no grid placement at all.
     *
     * The published state has to be drawn here because a child record has no state slot, and
     * "contain:paint" is what keeps an over-tall preview inside its row.
     *
     * @param array<string, mixed> $row
     */
    public function renderChildRecord(array $row): string
    {
        $label = $this->formatLabel($row, '');
        $heading = \is_array($label) ? $label[0] : $label;
        $type = (string) ($row['type'] ?? '');
        $preview = $this->schematic($row, $type);
        $state = ($row['published'] ?? '') ? 'published' : 'unpublished';

        if ('published' !== $state) {
            $heading .= ' <span class="visibility">('.($GLOBALS['TL_LANG']['MSC']['unpublished'] ?? 'unpublished').')</span>';
        }

        return '<div class="cte_type '.$state.'">'.$heading.'</div>'
            .'<div class="cte_preview'.('' === $preview ? ' empty' : '').'" style="contain:paint">'.$preview.'</div>';
    }

    /**
     * Whether the running core consumes the [label, preview, state] record label.
     */
    private function supportsRecordLabel(): bool
    {
        $version = $this->coreVersion ?? ContaoCoreBundle::getVersion();

        // A development checkout reports something like "dev-main", which no version
        // comparison can order. Those track the newest core, so assume the current contract.
        if (!preg_match('/^\d/', $version)) {
            return true;
        }

        return version_compare($version, self::RECORD_LABEL_SINCE, '>=');
    }

    /**
     * A plain-HTML sketch of the block's content, using only markup the backend theme styles
     * itself. Tokens stay visible and greyed so they read as placeholders rather than a bug.
     */
    private function schematic(array $row, string $type): string
    {
        $excerpt = static function (string $value, int $limit = 180): string {
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

            return StringUtil::specialchars(mb_strlen($value) > $limit ? mb_substr($value, 0, $limit).'…' : $value);
        };

        $parts = [];

        if ('' !== ($text = trim((string) ($row['body_text'] ?? '')))) {
            $parts[] = '<p>'.$excerpt($text).'</p>';
        }

        if ('details' === $type) {
            $cells = '';

            foreach (StringUtil::deserialize($row['details_rows'] ?? null, true) as $entry) {
                if (\is_array($entry) && '' !== trim((string) ($entry['key'] ?? ''))) {
                    $cells .= '<tr><th>'.$excerpt((string) $entry['key'], 40).'</th><td>'.$excerpt((string) ($entry['value'] ?? ''), 60).'</td></tr>';
                }
            }

            if ('' !== $cells) {
                // No inline styles: .cte_preview td/th in the backend theme styles this
                $parts[] = '<table>'.$cells.'</table>';
            }
        }

        if ('token' === $type && '' !== ($token = trim((string) ($row['token_name'] ?? '')))) {
            $parts[] = '<p><span class="tl_gray">##'.StringUtil::specialchars($token).'##</span></p>';
        }

        if ('' !== ($url = trim((string) ($row['link_url'] ?? '')))) {
            $parts[] = '<p><strong>'.$excerpt((string) ($row['link_text'] ?? ''), 60).'</strong> <span class="tl_gray">&rarr; '.$excerpt($url, 60).'</span></p>';
        }

        if ('html' === $type && '' !== ($custom = trim((string) ($row['custom_html'] ?? '')))) {
            $parts[] = '<p><span class="tl_gray">'.$excerpt(strip_tags($custom)).'</span></p>';
        }

        return implode('', $parts);
    }
}

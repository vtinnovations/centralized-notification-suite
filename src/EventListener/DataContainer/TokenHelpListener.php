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

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Token\TokenProviderInterface;
use VTInnovations\CentralizedNotificationSuite\Token\TokenRegistry;

/**
 * Fills the help wizard on the message body fields with the available ##tokens##, so an
 * editor can look up a token name instead of guessing it and finding out by sending.
 *
 * Runs on loadDataContainer rather than as an onload callback: the wizard is rendered by a
 * separate request (contao_backend_help), which loads the DCA but never builds a
 * DataContainer, so an onload callback would leave the popup empty.
 */
#[AsHook('loadDataContainer')]
class TokenHelpListener
{
    private const XPL_KEY = 'centralized_notification_suite_tokens';

    /**
     * The fields whose content is token-parsed.
     */
    /**
     * Token-carrying fields, per table.
     */
    private const FIELDS = [
        'tl_notification_message' => ['subject', 'text', 'html'],
        'tl_notification_block' => ['heading', 'body_text', 'link_text', 'link_url', 'details_rows', 'custom_html'],
    ];

    public function __construct(private readonly TokenRegistry $registry)
    {
    }

    public function __invoke(string $table): void
    {
        if (!isset(self::FIELDS[$table])) {
            return;
        }

        // The type labels double as the group headings below
        System::loadLanguageFile('tl_notification');

        $GLOBALS['TL_LANG']['XPL'][self::XPL_KEY] = $this->buildRows();

        foreach (self::FIELDS[$table] as $field) {
            // A block-contributed field only exists once its type is registered, so this has
            // to run after BlockDcaListener -- which is why that listener sits at priority 10.
            if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                $GLOBALS['TL_DCA'][$table]['fields'][$field]['explanation'] = self::XPL_KEY;
                $GLOBALS['TL_DCA'][$table]['fields'][$field]['eval']['helpwizard'] = true;
            }
        }
    }

    /**
     * Contao's be_help template prints each cell unescaped and renders a row as a full-width
     * heading when its first value is "headspan".
     *
     * @return list<array{0: string, 1: string}>
     */
    private function buildRows(): array
    {
        $grouped = $this->registry->getGroupedDefinitions();
        $typeLabels = $GLOBALS['TL_LANG']['tl_notification']['type_options'] ?? [];

        $rows = [$GLOBALS['TL_LANG']['tl_notification_message']['tokenHelpHeader'] ?? ['Token', 'Contains']];

        // Universal tokens go last: the editor came looking for the ones their trigger adds
        $universal = $grouped[TokenProviderInterface::TYPE_ANY] ?? [];
        unset($grouped[TokenProviderInterface::TYPE_ANY]);

        foreach (NotificationModel::TYPES as $type) {
            if ($definitions = $grouped[$type] ?? []) {
                $rows = [...$rows, ...$this->group((string) ($typeLabels[$type] ?? $type), $definitions)];
            }
        }

        if ($universal) {
            $label = $GLOBALS['TL_LANG']['tl_notification_message']['tokenHelpAny'] ?? 'Available to every notification';
            $rows = [...$rows, ...$this->group($label, $universal)];
        }

        return $rows;
    }

    /**
     * @param array<string, string> $definitions
     *
     * @return list<array{0: string, 1: string}>
     */
    private function group(string $heading, array $definitions): array
    {
        $rows = [['headspan', $this->escape($heading)]];

        foreach ($definitions as $token => $description) {
            // Token names such as "<field name>" would be swallowed as markup unescaped
            $rows[] = ['<code>##'.$this->escape((string) $token).'##</code>', $this->escape($description)];
        }

        return $rows;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

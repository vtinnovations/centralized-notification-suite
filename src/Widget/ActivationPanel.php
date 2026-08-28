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

namespace VTInnovations\CentralizedNotificationSuite\Widget;

use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;

/**
 * The status line and the two action buttons, rendered inside Contao's own settings form.
 *
 * There is no JavaScript here on purpose. The buttons are ordinary submit buttons in the form
 * Contao already renders, so they inherit its request token and its permission checks, and
 * they work whether or not an asset loaded or ran in the expected order. A button that cannot
 * fail to be wired is better than one that is wired cleverly.
 *
 * Each button carries a distinct value, so the handler can tell which operation was asked for
 * rather than guessing from which fields happen to be filled in.
 */
class ActivationPanel extends Widget
{
    /**
     * Renders inside the form rather than as a standalone field row.
     */
    protected $blnSubmitInput = false;

    protected $strTemplate = 'be_widget';

    public function generate(): string
    {
        $gate = System::getContainer()->get(ActivationGate::class);
        $activation = $gate->current();

        $labels = $GLOBALS['TL_LANG']['tl_settings'] ?? [];

        if ($activation->granted && null !== $activation->record) {
            $record = $activation->record;

            $rows = [
                ($labels['cnsStatus'] ?? 'Status') => $labels['cnsActive'] ?? 'Active',
                ($labels['cnsTier'] ?? 'Package') => $record->package,
                ($labels['cnsHost'] ?? 'Licensed host') => (string) $activation->host,
                ($labels['cnsHosts'] ?? 'Covered hosts') => implode(', ', $record->hosts),
                ($labels['cnsVersion'] ?? 'Version') => (string) $record->version,
                ($labels['cnsTerm'] ?? 'Term') => $record->lifetime
                    ? ($labels['cnsPerpetual'] ?? 'Perpetual')
                    : date('Y-m-d', (int) $record->expiresAt),
            ];
        } else {
            $rows = [
                ($labels['cnsStatus'] ?? 'Status') => $labels['cnsInactive'] ?? 'Not activated',
                ($labels['cnsDetail'] ?? 'Detail') => $this->explain($activation->reason, $labels),
            ];
        }

        $html = '<div class="widget cns-panel"><table class="tl_show" style="width:100%">';

        foreach ($rows as $label => $value) {
            $html .= \sprintf(
                '<tr><td class="tl_label" style="width:14em">%s</td><td>%s</td></tr>',
                StringUtil::specialchars((string) $label),
                StringUtil::specialchars((string) $value),
            );
        }

        $html .= '</table>';

        // The key is never rendered back. An administrator who needs to change it types a new
        // one; echoing it into the page would put it in browser history, caches and screen
        // shares for no benefit.
        $html .= '<div style="margin-top:.75rem">';

        if ($activation->granted) {
            $html .= \sprintf(
                '<button type="submit" name="cns_op" value="refresh" class="tl_submit">%s</button> ',
                StringUtil::specialchars($labels['cnsRefresh'] ?? 'Update licence'),
            );
        }

        if (null !== System::getContainer()->get(ActivationGate::class)->current()->record || $activation->granted) {
            $html .= \sprintf(
                '<button type="submit" name="cns_op" value="remove" class="tl_submit" onclick="return confirm(\'%s\')">%s</button>',
                StringUtil::specialchars($labels['cnsRemoveConfirm'] ?? 'Remove the stored licence?'),
                StringUtil::specialchars($labels['cnsRemove'] ?? 'Remove licence'),
            );
        }

        return $html.'</div></div>';
    }

    /**
     * Turns an internal category into one sentence an administrator can act on.
     *
     * Categories that would help someone probing keys -- which part of a rejected record was
     * wrong -- all collapse into the same generic line.
     *
     * @param array<string, mixed> $labels
     */
    private function explain(string $reason, array $labels): string
    {
        $known = [
            'no_record' => $labels['cnsReasonNone'] ?? 'No licence has been activated yet.',
            'no_configured_host' => $labels['cnsReasonHost'] ?? 'No website root page has a domain configured, or the stored licence does not cover any configured domain.',
            'expired' => $labels['cnsReasonExpired'] ?? 'The stored licence has expired.',
            'not_yet_valid' => $labels['cnsReasonFuture'] ?? 'The stored licence is not valid yet.',
        ];

        return (string) ($known[$reason] ?? ($labels['cnsReasonGeneric'] ?? 'The stored licence could not be verified. Please activate it again.'));
    }
}

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
 * The whole licence card -- status, key field and the three actions -- rendered inside Contao's
 * own settings form.
 *
 * The card is one boxed block per product, which is the shape every V-T.ONE package uses in the
 * licence manager: product name above the box, state on the first line, then the key field and
 * the three buttons in a row. An administrator running several of our packages should not have
 * to relearn the screen for each one.
 *
 * The key field lives here rather than in a DCA field of its own. A DCA text field is
 * repopulated from the POST after a save, so the key an administrator had just entered stayed
 * on screen -- in browser history, in screen shares and in any page cache. This input is
 * rendered with an empty value every time, and the stored key is never read back into it.
 *
 * There is no JavaScript here on purpose. The buttons are ordinary submit buttons in the form
 * Contao already renders, so they inherit its request token and its permission checks, and they
 * work whether or not an asset loaded or ran in the expected order. A button that cannot fail to
 * be wired is better than one that is wired cleverly.
 *
 * Each button carries a distinct value, so the handler can tell which operation was asked for
 * rather than guessing from which fields happen to be filled in.
 */
class ActivationPanel extends Widget
{
    /**
     * The name the handler reads the entered key from.
     */
    public const KEY_FIELD = 'cns_licence_key';

    /**
     * Renders inside the form rather than as a standalone field row.
     */
    protected $blnSubmitInput = false;

    protected $strTemplate = 'be_widget';

    public function generate(): string
    {
        $activation = System::getContainer()->get(ActivationGate::class)->current();

        $labels = $GLOBALS['TL_LANG']['tl_settings'] ?? [];

        $html = '<div class="widget cns-licence">'.$this->styles();

        // Pressing Enter in any settings field submits the form through its first submit
        // button. Without this one that would be "Verify and activate licence", so a stray
        // Enter anywhere on the Settings screen would post an empty key and raise an error.
        $html .= '<button type="submit" name="cns_noop" value="1" tabindex="-1" aria-hidden="true" class="cns-licence__noop"></button>';

        $html .= '<div class="cns-licence__box">';

        if ($activation->granted && null !== $activation->record) {
            $record = $activation->record;

            $html .= $this->state('on', (string) ($labels['cnsActive'] ?? 'Licence active'));
            $html .= $this->facts([
                ($labels['cnsTier'] ?? 'Package') => $record->package,
                ($labels['cnsHost'] ?? 'Licensed host') => (string) $activation->host,
                ($labels['cnsHosts'] ?? 'Covered hosts') => implode(', ', $record->hosts),
                ($labels['cnsVersion'] ?? 'Version') => (string) $record->version,
                ($labels['cnsTerm'] ?? 'Term') => $record->lifetime
                    ? ($labels['cnsPerpetual'] ?? 'Perpetual')
                    : date('Y-m-d', (int) $record->expiresAt),
            ]);
        } else {
            $html .= $this->state('off', (string) ($labels['cnsInactive'] ?? 'No active licence'));
            $html .= \sprintf(
                '<p class="cns-licence__detail">%s</p>',
                StringUtil::specialchars($this->explain($activation->reason, $labels)),
            );
        }

        $html .= $this->keyField($labels);
        $html .= $this->actions($labels);

        return $html.'</div></div>';
    }

    /**
     * The one-line verdict, in the same place whatever the state, so the eye does not have to
     * hunt for it when several products are listed under each other.
     */
    private function state(string $variant, string $text): string
    {
        return \sprintf(
            '<p class="cns-licence__state cns-licence__state--%s">%s</p>',
            $variant,
            StringUtil::specialchars($text),
        );
    }

    /**
     * @param array<string, string> $rows
     */
    private function facts(array $rows): string
    {
        $html = '<table class="cns-licence__facts">';

        foreach ($rows as $name => $value) {
            $html .= \sprintf(
                '<tr><th>%s</th><td>%s</td></tr>',
                StringUtil::specialchars((string) $name),
                StringUtil::specialchars((string) $value),
            );
        }

        return $html.'</table>';
    }

    /**
     * Always empty, always without autocomplete: the field is for entering a replacement, never
     * for displaying what is already stored.
     *
     * @param array<string, mixed> $labels
     */
    private function keyField(array $labels): string
    {
        $help = (string) ($labels['cnsKeyHelp'] ?? '');

        $html = \sprintf(
            '<label class="cns-licence__label" for="ctrl_%1$s">%2$s</label>'
            .'<input type="text" name="%1$s" id="ctrl_%1$s" class="tl_text cns-licence__key" value=""'
            .' placeholder="%3$s" autocomplete="off" spellcheck="false" maxlength="255">',
            self::KEY_FIELD,
            StringUtil::specialchars((string) ($labels['cnsKeyLabel'] ?? 'Licence key')),
            StringUtil::specialchars((string) ($labels['cnsKeyPlaceholder'] ?? 'XXXXX-XXXXX-XXXXX-XXXXX')),
        );

        // Appended rather than folded into the format above: a translation containing a per-cent
        // sign would otherwise be read as a conversion specification.
        if ('' !== $help) {
            $html .= '<p class="tl_help cns-licence__help">'.StringUtil::specialchars($help).'</p>';
        }

        return $html;
    }

    /**
     * All three actions are always rendered.
     *
     * Hiding "Remove licence" until a licence verifies is what left an administrator with an
     * expired or host-mismatched record unable to clear it -- the one state in which they most
     * need to. Removal is local and idempotent, so it is safe to offer at any time.
     *
     * @param array<string, mixed> $labels
     */
    private function actions(array $labels): string
    {
        return \sprintf(
            '<div class="cns-licence__actions">'
            .'<button type="submit" name="cns_op" value="activate" class="tl_submit">%s</button>'
            .'<button type="submit" name="cns_op" value="refresh" class="tl_submit">%s</button>'
            .'<button type="submit" name="cns_op" value="remove" class="tl_submit" onclick="return confirm(\'%s\')">%s</button>'
            .'</div>',
            StringUtil::specialchars((string) ($labels['cnsActivate'] ?? 'Verify and activate licence')),
            StringUtil::specialchars((string) ($labels['cnsRefresh'] ?? 'Update licence')),
            StringUtil::specialchars((string) ($labels['cnsRemoveConfirm'] ?? 'Remove the stored licence?')),
            StringUtil::specialchars((string) ($labels['cnsRemove'] ?? 'Remove licence')),
        );
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
            // Withdrawn by the issuer rather than merely unverifiable, so the administrator is
            // told which of the two it is instead of being sent to check a key that is fine.
            'revoked' => $labels['cnsReasonRevoked'] ?? 'The licence for this website has been withdrawn. Please contact us if you believe this is a mistake.',
            'superseded' => $labels['cnsReasonSuperseded'] ?? 'The stored licence is older than the last one this installation received, so it has been ignored. Activate the current licence again.',
            'refresh_overdue' => $labels['cnsReasonStale'] ?? 'The licence has not been re-checked within the required period. Use "Update Licence" once this website can reach the licence service again.',
        ];

        return (string) ($known[$reason] ?? ($labels['cnsReasonGeneric'] ?? 'The stored licence could not be verified. Please activate it again.'));
    }

    /**
     * Colours are stated rather than taken from Contao's message classes, which paint a full
     * banner. Both tones are readable on the light and the dark backend theme.
     */
    private function styles(): string
    {
        return '<style>'
            .'.cns-licence__noop{position:absolute;left:-9999px;width:1px;height:1px}'
            .'.cns-licence__box{border:1px solid rgba(127,127,127,.45);border-radius:3px;padding:12px 14px;max-width:640px}'
            .'.cns-licence__state{margin:0 0 8px;font-weight:700}'
            .'.cns-licence__state--off{color:#e04b4b}'
            .'.cns-licence__state--on{color:#589b0e}'
            .'.cns-licence__detail{margin:0 0 12px}'
            .'.cns-licence__facts{border-collapse:collapse;margin:0 0 12px;font-size:12px}'
            .'.cns-licence__facts th{text-align:left;padding:2px 18px 2px 0;font-weight:600;opacity:.75;vertical-align:top;white-space:nowrap}'
            .'.cns-licence__facts td{padding:2px 0;word-break:break-all}'
            .'.cns-licence__label{display:block;margin:0 0 4px;font-weight:600}'
            .'.cns-licence__key{width:100%;box-sizing:border-box}'
            .'.cns-licence__help{margin:4px 0 12px}'
            .'.cns-licence__actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:12px}'
            .'</style>';
    }
}

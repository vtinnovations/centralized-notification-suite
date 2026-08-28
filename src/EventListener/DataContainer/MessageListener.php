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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use VTInnovations\CentralizedNotificationSuite\Gateway\GatewayRegistry;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;

/**
 * The preview and test-send buttons, and the list label.
 *
 * The label carries a warning marker when a message cannot currently be delivered -- an
 * unpublished or deleted sender. Without it that failure is invisible until someone
 * notices the mail never arrived.
 */
class MessageListener
{
    /**
     * The preview glyph, drawn inline rather than taken from the core icon set.
     *
     * Contao ships a preview.svg, but in 5.3 to 5.6 it is stroked #fff because core only ever
     * used it on the dark preview toolbar -- in a light list row it renders invisible, and
     * there is no preview--dark.svg to fall back to either. 5.7 restroked it #222. Drawing it
     * here with currentColor sidesteps the difference: it follows the theme's text colour, so
     * it is legible on every supported core in both light and dark mode, and it needs no
     * assets:install the way a bundled icon file would.
     *
     * resend.svg (the test-send button) is deliberately still taken from core: it is opaque in
     * every version, so there is nothing to work around.
     */
    private const PREVIEW_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:text-bottom" role="img" aria-label="%s"><rect width="20" height="14" x="2" y="3" rx="2" ry="2"/><path d="M8 21h8m-4-4v4"/></svg>';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly GatewayRegistry $gateways,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    #[AsCallback(table: 'tl_notification_message', target: 'list.operations.preview.button')]
    public function previewButton(array $row, string|null $href, string $label, string $title, string|null $icon, string $attributes): string
    {
        return $this->popup(
            'centralized_notification_suite_preview',
            $row,
            $title,
            $attributes,
            \sprintf(self::PREVIEW_ICON, StringUtil::specialchars($label)),
            900,
            800,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    #[AsCallback(table: 'tl_notification_message', target: 'list.operations.testsend.button')]
    public function testSendButton(array $row, string|null $href, string $label, string $title, string|null $icon, string $attributes): string
    {
        return $this->popup(
            'centralized_notification_suite_test_send',
            $row,
            $title,
            $attributes,
            Image::getHtml((string) $icon, $label),
            720,
            640,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function popup(string $route, array $row, string $title, string $attributes, string $iconHtml, int $width, int $height): string
    {
        $url = $this->urlGenerator->generate($route, ['id' => (int) $row['id']]);

        return \sprintf(
            '<a href="%s" title="%s" target="_blank" rel="noopener" onclick="window.open(this.href,\'\',\'width=%d,height=%d,resizable=yes\');return false"%s>%s</a> ',
            StringUtil::specialchars($url),
            StringUtil::specialchars($title),
            $width,
            $height,
            $attributes,
            $iconHtml,
        );
    }

    /**
     * Flags a message whose sender cannot deliver it.
     *
     * Every parameter after $label is optional because the cores disagree on how many they
     * pass to a MODE_PARENT label callback: Contao 5.7 has one generic branch that passes
     * ($row, $label, $dc, $args), while 5.3 to 5.6 have a dedicated parent branch that passes
     * only ($row, $label, $dc). Requiring the fourth is a fatal "Too few arguments" on those
     * cores, which takes the whole module down rather than just the label.
     *
     * Returns the decorated $label rather than $args, which is what both parent views want:
     * 5.7 reads a string straight through (an array would be reduced to its first element,
     * losing the subject), and 5.3 concatenates it into the row (an array would surface as
     * the literal string "Array").
     *
     * @param array<string, mixed> $row
     * @param array<int, string>   $args
     */
    #[AsCallback(table: 'tl_notification_message', target: 'list.label.label')]
    public function formatLabel(array $row, string $label, DataContainer|null $dc = null, array $args = []): string
    {
        $problem = $this->findProblem($row);

        if (null === $problem) {
            return $label;
        }

        return \sprintf(
            '<span style="color:#c62828" title="%s">&#9888; %s</span>',
            StringUtil::specialchars($problem),
            $label,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function findProblem(array $row): string|null
    {
        $lang = $GLOBALS['TL_LANG']['tl_notification_message'] ?? [];
        $gateway = GatewayModel::findByPk((int) ($row['gateway'] ?? 0));

        if (!$gateway) {
            return $lang['gatewayMissing'] ?? 'The sender assigned to this message no longer exists, so it will not be sent.';
        }

        if (!$gateway->published) {
            return \sprintf(
                $lang['gatewayUnpublished'] ?? 'The sender "%s" is not published, so this message will not be sent.',
                (string) $gateway->title,
            );
        }

        if (!$this->gateways->has((string) $gateway->type)) {
            return \sprintf(
                $lang['gatewayTypeMissing'] ?? 'No gateway is installed for type "%s", so this message will not be sent.',
                (string) $gateway->type,
            );
        }

        // Only worth reporting for a gateway that actually delivers to them. A webhook or
        // file gateway has its destination in its own configuration, which is why the field
        // is not mandatory in the first place.
        if (
            $this->gateways->get((string) $gateway->type)->addressesRecipients()
            && '' === trim((string) ($row['recipients'] ?? ''))
        ) {
            return $lang['recipientsMissing'] ?? 'This message has no recipients, so it will not be sent.';
        }

        return null;
    }
}

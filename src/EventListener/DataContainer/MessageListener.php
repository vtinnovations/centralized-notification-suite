<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use VTInnovations\SimpleNotifyBundle\Gateway\GatewayRegistry;
use VTInnovations\SimpleNotifyBundle\Model\GatewayModel;

/**
 * The preview and test-send buttons, and the list label.
 *
 * The label carries a warning marker when a message cannot currently be delivered -- an
 * unpublished or deleted sender. Without it that failure is invisible until someone
 * notices the mail never arrived.
 */
class MessageListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly GatewayRegistry $gateways,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    #[AsCallback(table: 'tl_simple_message', target: 'list.operations.preview.button')]
    public function previewButton(array $row, string|null $href, string $label, string $title, string|null $icon, string $attributes): string
    {
        return $this->popup('simple_notify_preview', $row, $label, $title, $icon, $attributes, 900, 800);
    }

    /**
     * @param array<string, mixed> $row
     */
    #[AsCallback(table: 'tl_simple_message', target: 'list.operations.testsend.button')]
    public function testSendButton(array $row, string|null $href, string $label, string $title, string|null $icon, string $attributes): string
    {
        return $this->popup('simple_notify_test_send', $row, $label, $title, $icon, $attributes, 720, 640);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function popup(string $route, array $row, string $label, string $title, string|null $icon, string $attributes, int $width, int $height): string
    {
        $url = $this->urlGenerator->generate($route, ['id' => (int) $row['id']]);

        return \sprintf(
            '<a href="%s" title="%s" target="_blank" rel="noopener" onclick="window.open(this.href,\'\',\'width=%d,height=%d,resizable=yes\');return false"%s>%s</a> ',
            StringUtil::specialchars($url),
            StringUtil::specialchars($title),
            $width,
            $height,
            $attributes,
            Image::getHtml((string) $icon, $label),
        );
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string>   $args
     *
     * @return array<int, string>
     */
    #[AsCallback(table: 'tl_simple_message', target: 'list.label.label')]
    public function formatLabel(array $row, string $label, DataContainer $dc, array $args): array
    {
        $problem = $this->findProblem((int) $row['gateway']);

        if (null === $problem) {
            return $args;
        }

        $args[0] = \sprintf(
            '<span style="color:#c62828" title="%s">&#9888; %s</span>',
            StringUtil::specialchars($problem),
            $args[0] ?? '',
        );

        return $args;
    }

    private function findProblem(int $gatewayId): string|null
    {
        $lang = $GLOBALS['TL_LANG']['tl_simple_message'] ?? [];
        $gateway = GatewayModel::findByPk($gatewayId);

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

        return null;
    }
}

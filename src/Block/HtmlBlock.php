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

use VTInnovations\CentralizedNotificationSuite\Message\Branding;

/**
 * The escape hatch: markup written by hand.
 *
 * The field deliberately does NOT set preserveTags or useRawRequestData. Those two are what
 * the neighbouring tl_notification_message.html field uses to keep a designer's document
 * byte-for-byte, and they switch off Contao's input sanitisation -- so copying them here
 * would turn this field into an unfiltered HTML sink. Leaving them off means the value
 * arrives already stripped of <script>, <iframe> and on* handlers.
 *
 * <style> is stripped at render time on top of that: it survives Contao's sanitiser, and
 * HtmlRenderer would then hoist it out of the body and inline it against the whole document,
 * so a rule meant for one block would silently restyle the entire message.
 */
class HtmlBlock extends AbstractBlock
{
    public function getName(): string
    {
        return 'html';
    }

    public function getGroup(): string
    {
        return 'Advanced';
    }

    public function getConfigFields(): array
    {
        return [
            'custom_html' => [
                'exclude' => true,
                'inputType' => 'textarea',
                'eval' => ['rte' => 'ace|html', 'decodeEntities' => true, 'class' => 'monospace', 'tl_class' => 'clr long'],
                'sql' => 'text NULL',
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},custom_html';
    }

    public function render(array $row, Branding $branding): string
    {
        $html = (string) ($row['custom_html'] ?? '');

        if ('' === trim($html)) {
            return '';
        }

        $html = (string) preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);

        return '' !== trim($html) ? $html : '';
    }
}

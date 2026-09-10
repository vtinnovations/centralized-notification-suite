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
 * A call-to-action button.
 *
 * Padding sits on the cell, not on the anchor: Outlook's rendering engine ignores both
 * padding and display:inline-block on an <a>, which would collapse the button to a one-pixel
 * line of text. The trade-off is that in Outlook only the label itself is clickable.
 *
 * No VML, so Outlook renders square corners while everything else rounds them. VML would
 * double the markup for a cosmetic difference and is the usual source of "the button looks
 * wrong" reports.
 */
class ButtonBlock extends AbstractBlock
{
    public function getName(): string
    {
        return 'button';
    }

    public function getGroup(): string
    {
        return 'Action';
    }

    public function getConfigFields(): array
    {
        return [
            'button_style' => [
                'exclude' => true,
                'inputType' => 'select',
                'options' => ['solid', 'outline'],
                'reference' => &$GLOBALS['TL_LANG']['tl_notification_block']['button_style_options'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default 'solid'",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},link_text,link_url,button_style';
    }

    public function render(array $row, Branding $branding): string
    {
        $label = trim((string) ($row['link_text'] ?? ''));
        $url = $this->safeUrl((string) ($row['link_url'] ?? ''));

        if ('' === $label || '' === $url) {
            return '';
        }

        $brand = $branding->brandColor;
        $outline = 'outline' === ($row['button_style'] ?? 'solid');

        // bgcolor as an attribute as well as in CSS: Outlook honours the attribute, and
        // forced dark mode in Outlook.com flips the two together only when both are present.
        $cellStyle = $outline
            ? \sprintf('border-radius:6px;border:2px solid %s;background:#ffffff', $brand)
            : \sprintf('border-radius:6px;background:%s', $brand);

        $anchorStyle = \sprintf(
            'display:inline-block;padding:12px 26px;font-size:15px;font-weight:600;line-height:20px;color:%s;text-decoration:none;mso-line-height-rule:exactly',
            $outline ? $brand : $branding->onBrandColor(),
        );

        $button = \sprintf(
            '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt">'
            .'<tr><td align="center"%s style="%s"><a href="%s" style="%s">%s</a></td></tr></table>',
            $outline ? '' : ' bgcolor="'.$this->escape($brand).'"',
            $cellStyle,
            $this->escape($url),
            $anchorStyle,
            $this->escape($label),
        );

        // The button table is shrink-to-fit, so alignment happens on the row around it
        return $this->row($button, $this->align($row));
    }
}

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
 * Drops one generated token into the body -- a form summary, an upload list.
 *
 * Emits the literal ##token##, which MessageRenderer resolves in its single pass over the
 * composed body. That is the whole point: an editor gets a form summary table into the
 * message without typing markup or knowing the token's name by heart.
 */
class TokenBlock extends AbstractBlock
{
    public function getName(): string
    {
        return 'token';
    }

    public function getGroup(): string
    {
        return 'Text';
    }

    public function getConfigFields(): array
    {
        return [
            'token_name' => [
                'exclude' => true,
                'inputType' => 'select',
                // Options come from OptionsListener, which resolves them from the parent
                // notification's type so only tokens that can actually be filled are offered
                'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
                'sql' => "varchar(64) NOT NULL default ''",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},heading,token_name';
    }

    public function render(array $row, Branding $branding): string
    {
        $token = trim((string) ($row['token_name'] ?? ''));

        if ('' === $token || !preg_match('/^[A-Za-z0-9_]+$/', $token)) {
            return '';
        }

        $out = '';

        if ('' !== ($heading = trim((string) ($row['heading'] ?? '')))) {
            $out .= \sprintf(
                '<p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#11181c">%s</p>',
                $this->escape($heading),
            );
        }

        $out .= \sprintf(
            '<div style="font-size:15px;line-height:1.6;color:#1f2933;mso-line-height-rule:exactly">##%s##</div>',
            $token,
        );

        return $this->row($out, 'left');
    }
}

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

use Contao\StringUtil;
use VTInnovations\CentralizedNotificationSuite\Message\Branding;

/**
 * A label/value table -- an order summary, a booking, a form receipt.
 *
 * Deliberately not role="presentation": this is a real data table, so it keeps its <th
 * scope="row"> headers for screen readers. The cell styling matches the sample table the
 * preview uses for ##all_fields_html##, so a hand-built summary and an automatic one look
 * the same.
 *
 * Borders go on the cells, never on the table: Outlook.com strips table borders and Outlook
 * draws them differently. cellspacing="0" is required because Outlook ignores
 * border-collapse.
 */
class DetailsBlock extends AbstractBlock
{
    public function getName(): string
    {
        return 'details';
    }

    public function getGroup(): string
    {
        return 'Text';
    }

    public function getConfigFields(): array
    {
        return [
            'details_rows' => [
                'exclude' => true,
                'inputType' => 'keyValueWizard',
                'eval' => ['tl_class' => 'clr'],
                'sql' => 'text NULL',
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{content_legend},heading,details_rows';
    }

    public function render(array $row, Branding $branding): string
    {
        $cells = '';

        foreach (StringUtil::deserialize($row['details_rows'] ?? null, true) as $entry) {
            if (!\is_array($entry) || '' === trim((string) ($entry['key'] ?? ''))) {
                continue;
            }

            $cells .= \sprintf(
                '<tr><th scope="row" align="left" valign="top" style="padding:7px 10px;border:1px solid #e3e5e8;background:#f6f8fa;font-size:13px;color:#57606a;font-weight:600;text-align:left">%s</th>'
                .'<td valign="top" style="padding:7px 10px;border:1px solid #e3e5e8;font-size:14px;color:#1f2933">%s</td></tr>',
                $this->escape(trim((string) $entry['key'])),
                $this->multiline((string) ($entry['value'] ?? '')),
            );
        }

        if ('' === $cells) {
            return '';
        }

        $table = '<table width="100%" cellpadding="0" cellspacing="0" border="0"'
            .' style="width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt">'
            .$cells.'</table>';

        $heading = trim((string) ($row['heading'] ?? ''));

        if ('' !== $heading) {
            $table = \sprintf(
                '<p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#11181c">%s</p>',
                $this->escape($heading),
            ).$table;
        }

        return $this->row($table, 'left');
    }
}

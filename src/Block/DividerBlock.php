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
 * A horizontal rule.
 *
 * A bordered table cell rather than <hr>, which Outlook's rendering engine styles
 * unpredictably. The non-breaking space and the zeroed font metrics stop clients giving the
 * empty cell a line's worth of height.
 */
class DividerBlock extends AbstractBlock
{
    public function getName(): string
    {
        return 'divider';
    }

    public function getGroup(): string
    {
        return 'Layout';
    }

    public function getPalette(): string
    {
        return '';
    }

    public function render(array $row, Branding $branding): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"'
            .' style="width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt">'
            .'<tr><td style="border-top:1px solid #e3e7eb;font-size:0;line-height:0;height:1px">&nbsp;</td></tr>'
            .'</table>';
    }
}

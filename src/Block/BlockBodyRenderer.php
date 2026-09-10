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

use Contao\CoreBundle\Framework\ContaoFramework;
use VTInnovations\CentralizedNotificationSuite\Message\Branding;
use VTInnovations\CentralizedNotificationSuite\Model\BlockModel;

/**
 * Composes a message body from its blocks.
 *
 * Split into a DB-free compose() and a loading renderBody() so the composition rules can be
 * tested without booting the framework, the way DesignLibrary is.
 */
class BlockBodyRenderer
{
    /**
     * Upper bound for the per-block gap. An editor typing 9999 should not produce a mail with
     * a screen of whitespace in it.
     */
    private const MAX_SPACE = 80;

    public function __construct(
        private readonly BlockRegistry $blocks,
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * The body of one message, or '' when it has no usable blocks.
     */
    public function renderBody(int $messageId, Branding $branding): string
    {
        $this->framework->initialize();

        $blocks = BlockModel::findPublishedByPid($messageId);

        if (!$blocks) {
            return '';
        }

        $rows = [];

        foreach ($blocks as $block) {
            $rows[] = $block->row();
        }

        return $this->compose($rows, $branding);
    }

    /**
     * Composes already-ordered rows into body markup.
     *
     * An unregistered type is skipped rather than fatal: an uninstalled third-party block
     * must not stop a notification going out, which is the same policy DesignLibrary::build()
     * applies to an unknown design.
     *
     * @param list<array<string, mixed>> $rows
     */
    public function compose(array $rows, Branding $branding): string
    {
        $rendered = [];

        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');

            if (!$this->blocks->has($type)) {
                continue;
            }

            $markup = $this->blocks->get($type)->render($row, $branding);

            if ('' === trim($markup)) {
                continue;
            }

            $rendered[] = [$markup, $this->space($row)];
        }

        if (!$rendered) {
            return '';
        }

        // The design's .sn-body already pads the bottom of the card, so the last block adds
        // nothing of its own -- otherwise every message ends with a doubled gap.
        $rendered[\count($rendered) - 1][1] = 0;

        $body = '';

        foreach ($rendered as [$markup, $space]) {
            $body .= \sprintf(
                '<table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0" style="width:100%%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt">'
                .'<tr><td style="padding:0 0 %dpx">%s</td></tr></table>',
                $space,
                $markup,
            );
        }

        return $body;
    }

    /**
     * One block's markup, for the backend list card.
     */
    public function renderOne(array $row, Branding $branding): string
    {
        $type = (string) ($row['type'] ?? '');

        return $this->blocks->has($type) ? $this->blocks->get($type)->render($row, $branding) : '';
    }

    private function space(array $row): int
    {
        return max(0, min(self::MAX_SPACE, (int) ($row['space_after'] ?? 20)));
    }
}

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
 * A single image, optionally linked.
 *
 * The src is root-relative so ImageEmbedder can turn it into an inline cid: attachment when
 * the message has "Embed images" on -- which is what makes it appear without the recipient
 * allowing remote content, and what makes console sends work.
 *
 * display:block is not cosmetic: without it Gmail and Outlook.com leave a few pixels of
 * baseline gap under the image, made worse by the newline the CSS inliner introduces between
 * elements. The width attribute duplicates the CSS because Outlook ignores max-width.
 */
class ImageBlock extends AbstractBlock
{
    public function __construct(private readonly FileUrlResolver $files)
    {
    }

    public function getName(): string
    {
        return 'image';
    }

    public function getGroup(): string
    {
        return 'Media';
    }

    public function getPalette(): string
    {
        return '{content_legend},image,image_alt,image_width,link_url';
    }

    public function render(array $row, Branding $branding): string
    {
        $src = $this->files->resolve($row['image'] ?? null);

        if ('' === $src) {
            return '';
        }

        $width = $this->imageWidth($row);

        $img = \sprintf(
            '<img src="%s" width="%d" alt="%s" style="display:block;width:100%%;max-width:%dpx;height:auto;border:0;outline:none;text-decoration:none">',
            $this->escape($src),
            $width,
            $this->escape((string) ($row['image_alt'] ?? '')),
            $width,
        );

        if ('' !== ($url = $this->safeUrl((string) ($row['link_url'] ?? '')))) {
            $img = \sprintf('<a href="%s" style="text-decoration:none;border:0">%s</a>', $this->escape($url), $img);
        }

        return $this->row($img, $this->align($row));
    }
}

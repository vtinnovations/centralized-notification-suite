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

use Contao\FilesModel;
use Contao\StringUtil;

/**
 * Turns a file picker's UUID into a src a block can emit.
 *
 * Root-relative, not absolute, for the same two reasons BrandingProvider::resolveLogo()
 * gives: ImageEmbedder maps such a src onto the file on disk and rewrites it to an inline
 * cid: attachment, so the image shows without the recipient allowing remote content -- and
 * it works when the notification is sent from the console, where there is no request to
 * build a host from.
 */
class FileUrlResolver
{
    public function resolve(mixed $uuid): string
    {
        if (!$uuid) {
            return '';
        }

        $file = FilesModel::findByUuid(StringUtil::binToUuid($uuid));

        if (!$file || !is_file($file->getAbsolutePath())) {
            return '';
        }

        return '/'.ltrim($file->path, '/');
    }
}

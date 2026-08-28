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

namespace VTInnovations\CentralizedNotificationSuite\Message;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\StringUtil;
use VTInnovations\CentralizedNotificationSuite\Model\BrandingModel;

/**
 * Loads the branding record and turns it into a Branding value object.
 *
 * Cached for the request: one send renders subject, text and HTML, and a message with three
 * designs assigned would otherwise re-read the same row for each of them.
 */
class BrandingProvider
{
    private Branding|null $cached = null;

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function get(): Branding
    {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $this->framework->initialize();

        $model = BrandingModel::findByPk(BrandingModel::ID);

        if (!$model) {
            // Nothing configured yet: the designs still have to render, so fall back to the
            // defaults rather than producing an empty document.
            return $this->cached = new Branding();
        }

        return $this->cached = new Branding(
            logoUrl: $this->resolveLogo($model->logo),
            logoWidth: (int) ($model->logo_width ?: 140),
            brandColor: (string) ($model->brand_color ?: '#0b5fff'),
            companyName: (string) $model->company_name,
            website: (string) $model->website,
            supportEmail: (string) $model->support_email,
            address: (string) $model->address,
            footerNote: (string) $model->footer_note,
        );
    }

    /**
     * Resolves the file picker's UUID to a root-relative path.
     *
     * Root-relative rather than absolute: ImageEmbedder maps such a src onto the file on
     * disk and turns it into an inline attachment, which is what makes the logo appear
     * without the recipient allowing remote content -- and what makes it work when the
     * notification is sent from the console, where there is no request to build a host from.
     */
    private function resolveLogo(mixed $uuid): string
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

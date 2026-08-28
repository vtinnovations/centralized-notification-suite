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

namespace VTInnovations\CentralizedNotificationSuite\Model;

use Contao\Model;

/**
 * The single branding record every design reads from.
 *
 * One row rather than a field set per layout: a site has one brand, and asking an editor to
 * re-upload the logo on each of twenty layouts is how logos end up out of date.
 *
 * @property int         $id
 * @property int         $tstamp
 * @property string|null $logo
 * @property int         $logo_width
 * @property string      $brand_color
 * @property string      $company_name
 * @property string|null $address
 * @property string      $website
 * @property string      $support_email
 * @property string|null $footer_note
 *
 * @method static BrandingModel|null findByPk($id, array $opt = [])
 */
class BrandingModel extends Model
{
    /**
     * The branding is a singleton, so its id never varies.
     */
    public const ID = 1;

    protected static $strTable = 'tl_notification_branding';

    /**
     * Returns the branding row, creating it on first use so the backend always has
     * something to edit and the renderer always has something to read.
     */
    public static function findSingleOrCreate(): self
    {
        if ($existing = static::findByPk(self::ID)) {
            return $existing;
        }

        $model = new self();
        $model->id = self::ID;
        $model->tstamp = time();
        $model->save();

        return $model;
    }
}

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
use Contao\Model\Collection;

/**
 * A reusable e-mail layout: the branded frame that every message of a site shares, so the
 * header, footer and CSS are maintained in one place instead of pasted into each message.
 *
 * @property int         $id
 * @property int         $tstamp
 * @property string      $title
 * @property string      $preheader
 * @property string      $layout_mode
 * @property string|null $wrapper_html
 * @property string|null $header_html
 * @property string|null $footer_html
 * @property string|null $css
 * @property string      $inline_css
 * @property string      $published
 *
 * @method static TemplateModel|null findByPk($id, array $opt = [])
 */
class TemplateModel extends Model
{
    /**
     * Placeholder replaced with the message body. Not a simple token (##...##) on purpose:
     * it is structural, belongs to the template rather than the data, and must survive
     * token replacement untouched.
     */
    public const BODY_PLACEHOLDER = '##message_body##';

    protected static $strTable = 'tl_notification_template';

    public static function findAllPublished(): Collection|null
    {
        return static::findBy(
            ['tl_notification_template.published=1'],
            [],
            ['order' => 'tl_notification_template.title'],
        );
    }
}

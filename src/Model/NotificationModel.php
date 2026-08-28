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
 * @property int    $id
 * @property int    $tstamp
 * @property string $title
 * @property string $alias
 * @property string $type
 *
 * @method static NotificationModel|null findByPk($id, array $opt = [])
 * @method static NotificationModel|null findOneBy($col, $val, array $opt = [])
 * @method static NotificationModel|null findOneByAlias($val, array $opt = [])
 */
class NotificationModel extends Model
{
    /**
     * What triggers a notification. The type decides which tokens the backend offers and
     * which notifications a given trigger will list -- a form's notification picker showing
     * newsletter notifications is how editors end up with messages full of empty tokens.
     */
    public const TYPE_FORM = 'form';

    public const TYPE_MEMBER = 'member';

    public const TYPE_COMMENT = 'comment';

    public const TYPE_NEWSLETTER = 'newsletter';

    /** Triggered from project code via CentralizedNotificationSuite::send(). */
    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_FORM,
        self::TYPE_MEMBER,
        self::TYPE_COMMENT,
        self::TYPE_NEWSLETTER,
        self::TYPE_CUSTOM,
    ];

    protected static $strTable = 'tl_notification';

    public static function findByType(string $type): Collection|null
    {
        return static::findBy(
            ['tl_notification.type=?'],
            [$type],
            ['order' => 'tl_notification.title'],
        );
    }

    public static function findByAlias(string $alias): self|null
    {
        return static::findOneBy('alias', $alias);
    }
}

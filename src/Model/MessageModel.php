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

use Contao\Date;
use Contao\Model;
use Contao\Model\Collection;

/**
 * @property int    $id
 * @property int    $tstamp
 * @property int    $pid
 * @property int    $gateway
 * @property string $language
 * @property string $fallback
 * @property string $subject
 * @property string $text
 * @property string $body_mode
 * @property string $html
 * @property int    $template
 * @property string $auto_plaintext
 * @property string $embed_images
 * @property string $recipients
 * @property string $cc
 * @property string $bcc
 * @property string $reply_to
 * @property int    $priority
 * @property string|null $attachments
 * @property string $published
 * @property string $start
 * @property string $stop
 *
 * @method static MessageModel|null findByPk($id, array $opt = [])
 */
class MessageModel extends Model
{
    /** Body comes from the html field, as it always has. */
    public const BODY_MODE_HTML = 'html';

    /** Body is composed from the message's blocks. */
    public const BODY_MODE_BLOCKS = 'blocks';

    protected static $strTable = 'tl_notification_message';

    public static function findPublishedByPid(int $pid): Collection|null
    {
        $time = Date::floorToMinute();

        return static::findBy(
            [
                'tl_notification_message.pid=?',
                'tl_notification_message.published=1',
                "(tl_notification_message.start='' OR tl_notification_message.start<=$time)",
                "(tl_notification_message.stop='' OR tl_notification_message.stop>$time)",
            ],
            [$pid],
            ['order' => 'tl_notification_message.id'],
        );
    }
}

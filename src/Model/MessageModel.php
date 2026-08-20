<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Model;

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
    protected static $strTable = 'tl_simple_message';

    public static function findPublishedByPid(int $pid): Collection|null
    {
        $time = Date::floorToMinute();

        return static::findBy(
            [
                'tl_simple_message.pid=?',
                'tl_simple_message.published=1',
                "(tl_simple_message.start='' OR tl_simple_message.start<=$time)",
                "(tl_simple_message.stop='' OR tl_simple_message.stop>$time)",
            ],
            [$pid],
            ['order' => 'tl_simple_message.id'],
        );
    }
}

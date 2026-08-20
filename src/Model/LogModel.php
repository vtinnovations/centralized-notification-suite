<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Model;

use Contao\Model;
use Contao\Model\Collection;
use VTInnovations\SimpleNotifyBundle\SendResult;

/**
 * One row per send attempt. This is what makes "did the customer actually get the enquiry
 * mail?" an answerable question in the backend instead of a grep through var/logs.
 *
 * @property int         $id
 * @property int         $tstamp
 * @property int         $pid          tl_simple_notification.id
 * @property int         $message      tl_simple_message.id
 * @property string      $reference
 * @property string      $alias
 * @property int         $gateway      tl_simple_gateway.id
 * @property string      $gateway_type
 * @property string      $recipients
 * @property string      $subject
 * @property string      $status
 * @property string|null $error
 * @property string|null $body_html
 * @property string|null $body_text
 * @property string|null $envelope
 * @property int         $attempts
 * @property string      $source
 * @property int         $last_attempt
 *
 * @method static LogModel|null   findByPk($id, array $opt = [])
 * @method static Collection|null findByStatus($val, array $opt = [])
 */
class LogModel extends Model
{
    protected static $strTable = 'tl_simple_log';

    public static function findByReference(string $reference): self|null
    {
        if ('' === $reference) {
            return null;
        }

        return static::findOneBy('reference', $reference);
    }

    /**
     * Failed entries still worth another attempt, oldest first.
     */
    public static function findRetryable(int $maxAttempts, int $limit = 50): Collection|null
    {
        return static::findBy(
            [
                'tl_simple_log.status=?',
                'tl_simple_log.attempts<?',
                "(tl_simple_log.body_text != '' OR tl_simple_log.body_html != '')",
            ],
            [SendResult::STATUS_FAILED, $maxAttempts],
            ['order' => 'tl_simple_log.tstamp ASC', 'limit' => $limit],
        );
    }

    public static function countByStatus(string $status): int
    {
        return static::countBy('status', $status);
    }

    /**
     * Timestamp of the most recent attempt for a notification, for the "last sent" column.
     */
    public static function findLatestForNotification(int $pid): self|null
    {
        return static::findOneBy(
            ['tl_simple_log.pid=?'],
            [$pid],
            ['order' => 'tl_simple_log.tstamp DESC'],
        );
    }
}

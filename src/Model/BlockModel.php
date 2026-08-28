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
 * One block of a message body.
 *
 * @property int         $id
 * @property int         $pid
 * @property int         $sorting
 * @property int         $tstamp
 * @property string      $type
 * @property string      $heading
 * @property string|null $body_text
 * @property string      $link_text
 * @property string      $link_url
 * @property mixed       $image
 * @property string      $image_alt
 * @property int         $image_width
 * @property string      $align
 * @property int         $space_after
 * @property string      $published
 *
 * @method static BlockModel|null findByPk($id, array $opt = [])
 */
class BlockModel extends Model
{
    protected static $strTable = 'tl_notification_block';

    /**
     * The published blocks of a message, in editing order.
     *
     * Ordered by sorting and then id: Contao's cut/paste leaves gaps rather than a dense
     * sequence, and a seeded set can legitimately share a sorting value, so id is the
     * tie-breaker that keeps the order stable.
     *
     * published='1' rather than !='': DC_Table::toggle() stores PHP false, which a char(1)
     * column takes as '0', not an empty string.
     */
    public static function findPublishedByPid(int $pid): Collection|null
    {
        return static::findBy(
            [
                'tl_notification_block.pid=?',
                "tl_notification_block.published='1'",
            ],
            [$pid],
            ['order' => 'tl_notification_block.sorting, tl_notification_block.id'],
        );
    }
}

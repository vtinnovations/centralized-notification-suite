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
 * Gateway-specific columns (sender_email, mailer_transport, ...) are declared by the
 * gateway itself via GatewayInterface::getConfigFields() and merged into the DCA by
 * GatewayDcaListener, so they are listed here for IDE support only.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $title
 * @property string $type
 * @property string $sender_name
 * @property string $sender_email
 * @property string $reply_to
 * @property string $mailer_transport
 * @property string $published
 *
 * @method static GatewayModel|null findByPk($id, array $opt = [])
 * @method static Collection|null   findByPublished($val, array $opt = [])
 */
class GatewayModel extends Model
{
    protected static $strTable = 'tl_notification_gateway';

    public static function findAllPublished(): Collection|null
    {
        return static::findBy(
            ['tl_notification_gateway.published=1'],
            [],
            ['order' => 'tl_notification_gateway.title'],
        );
    }
}

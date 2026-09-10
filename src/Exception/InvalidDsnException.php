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

namespace VTInnovations\CentralizedNotificationSuite\Exception;

/**
 * Thrown when the SMTP settings cannot form a valid mailer DSN.
 */
class InvalidDsnException extends NotificationException
{
}

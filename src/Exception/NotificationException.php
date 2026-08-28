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
 * Thrown for configuration errors the caller can only fix in code or in the backend --
 * an unknown notification alias, for example. Delivery failures are deliberately *not*
 * exceptions: they are caught per message, logged and reported in the send result, so a
 * broken mail server can never turn a visitor's form submission into a 500.
 */
class NotificationException extends \RuntimeException
{
    public static function unknownAlias(string $alias): self
    {
        return new self(\sprintf('No notification found with alias "%s".', $alias));
    }

    public static function unknownGateway(string $type): self
    {
        return new self(\sprintf('No notification gateway registered for type "%s".', $type));
    }

    public static function unknownBlock(string $type): self
    {
        return new self(\sprintf('No notification block registered for type "%s".', $type));
    }
}

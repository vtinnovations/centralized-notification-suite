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

namespace VTInnovations\CentralizedNotificationSuite\Distribution;

/**
 * A package failed verification and must not be trusted for any purpose.
 *
 * The message is a fixed internal category such as "digest_mismatch" or "unknown_signing_key".
 * It exists so an operator can tell an empty key ring apart from a genuine forgery; it is
 * never rendered to a browser, never carries remote content, and never authorises anything.
 * Categories are diagnostic; none of them is a bypass.
 */
class UnverifiedPackage extends \RuntimeException
{
    public function category(): string
    {
        return $this->getMessage();
    }
}

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

namespace VTInnovations\CentralizedNotificationSuite\Mailer;

/**
 * Outcome of saving the SMTP settings, as the backend module needs to report it.
 */
class HandleResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message = '',
    ) {
    }
}

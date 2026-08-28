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
 * Outcome of a test send against operator-supplied SMTP credentials.
 */
class TestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string|null $error = null,
        public readonly float $duration = 0.0,
    ) {
    }
}

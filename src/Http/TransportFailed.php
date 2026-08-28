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

namespace VTInnovations\CentralizedNotificationSuite\Http;

/**
 * An outbound call did not produce a usable answer.
 *
 * Always a fixed category -- "unreachable", "http_503", "response_too_large" -- and never the
 * remote body or the underlying client's message, both of which can carry internal paths.
 *
 * A failure of this kind says nothing about whether a stored record is valid. Callers must
 * leave existing state untouched: a network outage is not a reason to switch a working site
 * off.
 */
class TransportFailed extends \RuntimeException
{
    public function category(): string
    {
        return $this->getMessage();
    }
}

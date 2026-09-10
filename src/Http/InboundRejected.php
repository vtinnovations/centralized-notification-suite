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
 * An inbound update request could not be authenticated.
 *
 * The category stays on the server. What goes back to the caller is a bare 401 or 403 with no
 * detail: telling an unauthenticated caller whether their timestamp, nonce, key id or
 * signature was the problem hands them a tool for probing the endpoint.
 */
class InboundRejected extends \RuntimeException
{
    public function category(): string
    {
        return $this->getMessage();
    }
}

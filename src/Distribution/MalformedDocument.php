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
 * A received document could not be parsed or re-encoded deterministically.
 *
 * Carries no remote content: the message is written for an operator reading an internal
 * category, never for a browser response, and must not quote the offending bytes.
 */
class MalformedDocument extends \RuntimeException
{
}

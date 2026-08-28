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

namespace VTInnovations\CentralizedNotificationSuite\Store;

/**
 * The stored state could not be replaced.
 *
 * Raised only for local storage faults -- an unwritable directory, a short write, a failed
 * swap. It never means the record was rejected, and it must leave whatever was previously
 * stored exactly as it was.
 */
class StateNotWritable extends \RuntimeException
{
}

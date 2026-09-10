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

namespace VTInnovations\CentralizedNotificationSuite\Runtime;

/**
 * An activation, refresh or removal did not take effect.
 *
 * The message is a fixed internal category. Administrators are shown one generic sentence
 * instead, because the precise reason can distinguish "this key is unknown" from "this key
 * belongs to another host", and that difference is useful to someone probing keys.
 */
class ActivationRefused extends \RuntimeException
{
    public function __construct(string $category, \Throwable|null $previous = null)
    {
        parent::__construct($category, 0, $previous);
    }

    public function category(): string
    {
        return $this->getMessage();
    }
}

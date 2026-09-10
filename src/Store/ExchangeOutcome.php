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
 * What the journal made of an inbound request id.
 *
 * Three outcomes, because the wire format distinguishes them and collapsing any two would
 * either apply an update twice or refuse a legitimate retry.
 */
final class ExchangeOutcome
{
    private function __construct(
        public readonly bool $isNew,
        public readonly bool $isRepeat,
        public readonly int $appliedVersion,
    ) {
    }

    /**
     * Not seen before: process it.
     */
    public static function fresh(): self
    {
        return new self(true, false, 0);
    }

    /**
     * Byte-identical repeat of something already handled: answer as before, change nothing.
     */
    public static function repeat(int $appliedVersion): self
    {
        return new self(false, true, $appliedVersion);
    }

    /**
     * Same id, different content. A replay attempt, and refused as one.
     */
    public static function conflict(): self
    {
        return new self(false, false, 0);
    }
}

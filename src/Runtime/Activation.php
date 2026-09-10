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
 * The decision, as an immutable value.
 *
 * Deliberately not a bare boolean. A single mutable flag is the thing an attacker looks for:
 * flip it once and everything opens. This carries the record it was derived from, so each
 * protected feature can ask its own question rather than trusting one shared switch, and it
 * cannot be edited after the fact.
 *
 * The reason is an internal category for the administration screen and operational logs. It
 * explains nothing to an attacker that they could not already determine, and it never
 * authorises anything on its own.
 */
final class Activation
{
    private function __construct(
        public readonly bool $granted,
        public readonly string $reason,
        public readonly ActivationRecord|null $record,
        public readonly string|null $host,
    ) {
    }

    public static function granted(ActivationRecord $record, string $host): self
    {
        return new self(true, 'active', $record, $host);
    }

    public static function none(string $reason): self
    {
        return new self(false, $reason, null, null);
    }

    /**
     * Whether one named capability is included.
     *
     * An empty feature list means the tier grants its whole documented feature set rather
     * than nothing -- the issuer uses the list to carve out extras, not to enumerate the
     * basics. An unlicensed installation always answers false, whatever the list said.
     */
    public function allows(string $feature): bool
    {
        if (!$this->granted || null === $this->record) {
            return false;
        }

        return [] === $this->record->features || \in_array($feature, $this->record->features, true);
    }

    /**
     * The stored key, for the one signal that is permitted to carry it.
     *
     * Only ever non-null when the record behind it verified, so a key can never be sent for
     * an installation that is not actually licensed.
     */
    public function key(): string|null
    {
        return $this->granted && null !== $this->record ? $this->record->key : null;
    }

    public function version(): int
    {
        return $this->record?->version ?? 0;
    }
}

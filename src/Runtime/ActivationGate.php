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

use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Distribution\MalformedDocument;
use VTInnovations\CentralizedNotificationSuite\Distribution\SealedPackage;
use VTInnovations\CentralizedNotificationSuite\Distribution\UnverifiedPackage;
use VTInnovations\CentralizedNotificationSuite\Store\ActivationStore;

/**
 * Answers "may this installation use the product right now?", from stored state alone.
 *
 * The stored record is re-verified on the way out, every time -- signatures, digest, tier,
 * dates and host. It is not trusted because it is on disk: the disk is exactly where an
 * attacker would put a record of their own.
 *
 * The answer is computed once per request and reused, because the same question is asked at
 * several feature boundaries and re-reading and re-verifying at each of them would be waste.
 * It is cached in memory only; nothing here writes a "we already decided this" marker that a
 * later request could be made to trust.
 *
 * This class decides; it does not enforce. Enforcement lives next to each protected feature,
 * so that removing one service cannot switch every gate off at once.
 */
class ActivationGate
{
    private Activation|null $decided = null;

    public function __construct(
        private readonly ActivationStore $store,
        private readonly IssuerKeyring $keys,
        private readonly InstallationHosts $hosts,
    ) {
    }

    /**
     * The current decision, with the reason attached for the administration screen.
     */
    public function current(): Activation
    {
        return $this->decided ??= $this->decide();
    }

    /**
     * Forgets the cached decision after the stored state has changed.
     *
     * Only affects this request. The next request re-reads and re-verifies from scratch.
     */
    public function forget(): void
    {
        $this->decided = null;
    }

    /**
     * The stored record, re-verified, whatever it grants.
     *
     * Separate from current() because the two answer different questions. current() answers
     * "may this installation use the product", and deliberately discards a record that grants
     * nothing. The refresh job needs the record itself -- particularly once the lease has run
     * out and the answer to the first question has become no, since that is exactly when it
     * has to keep asking the issuer for a newer state.
     */
    public function storedRecord(int|null $now = null): ActivationRecord|null
    {
        $stored = $this->store->read();

        if (null === $stored) {
            return null;
        }

        $now ??= time();

        try {
            $package = SealedPackage::open(
                (object) [
                    'license_payload_b64' => base64_encode($stored['bytes']),
                    'integrity' => $stored['seal'],
                ],
                $this->keys,
                $now,
            );

            return ActivationRecord::fromDocument($package->document, $now);
        } catch (UnverifiedPackage|RecordNotApplicable|MalformedDocument) {
            return null;
        }
    }

    private function decide(): Activation
    {
        $stored = $this->store->read();

        if (null === $stored) {
            return Activation::none('no_record');
        }

        $now = time();

        try {
            // Re-verified from the stored bytes, not merely re-read. A hand-edited record and
            // a stale seal both fail here rather than being taken at face value.
            $package = SealedPackage::open(
                (object) [
                    'license_payload_b64' => base64_encode($stored['bytes']),
                    'integrity' => $stored['seal'],
                ],
                $this->keys,
                $now,
            );

            $record = ActivationRecord::fromDocument($package->document, $now);
        } catch (UnverifiedPackage|RecordNotApplicable|MalformedDocument $e) {
            return Activation::none($e instanceof MalformedDocument ? 'unreadable_record' : $e->getMessage());
        }

        // Measured against the highest version ever accepted, before anything else is read.
        // This is what stops a revocation being undone by restoring an older record.json from
        // a backup: that file is genuine and every signature on it holds, so the only thing
        // that can tell it apart from the current one is that it is older.
        $watermark = $this->store->watermark();

        if (null !== $watermark) {
            if ($record->version < $watermark['version']) {
                return Activation::none('superseded');
            }

            // At the same version the stored status is authoritative, so a valid record cannot
            // be swapped in over a withdrawal issued at that same revision.
            if ($record->version === $watermark['version'] && ProductProfile::STATUS_VALID !== $watermark['status']) {
                return Activation::none($watermark['status']);
            }
        }

        // A record that withdraws is genuine and applies to this installation; it simply
        // grants nothing. The reason is carried through so the settings screen can say which
        // of the two it is rather than showing one blank "not activated".
        if (!$record->positive()) {
            return Activation::none($record->status);
        }

        // The record has to authorise a host this installation is actually configured for.
        // Copying a valid record to another site therefore fails here, before any feature
        // boundary is reached.
        $matched = $this->hosts->intersect($record->hosts);

        if ([] === $matched) {
            return Activation::none('no_configured_host');
        }

        // The signed lease. Past the grace cutoff the installation must have obtained a newer
        // state, so a site that never receives a pushed withdrawal -- offline, firewalled, or
        // deliberately refusing inbound requests -- still stops granting on its own.
        if (null !== $record->graceUntil && $now >= $record->graceUntil) {
            return Activation::none('refresh_overdue');
        }

        // Deterministic so background work and the session signal do not depend on whichever
        // host a later request happens to arrive on.
        $host = $this->hosts->currentTrusted();
        $host = null !== $host && \in_array($host, $matched, true) ? $host : $matched[0];

        return Activation::granted($record, $host);
    }
}

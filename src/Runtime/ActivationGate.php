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

        // The record has to authorise a host this installation is actually configured for.
        // Copying a valid record to another site therefore fails here, before any feature
        // boundary is reached.
        $matched = $this->hosts->intersect($record->hosts);

        if ([] === $matched) {
            return Activation::none('no_configured_host');
        }

        // Deterministic so background work and the session signal do not depend on whichever
        // host a later request happens to arrive on.
        $host = $this->hosts->currentTrusted();
        $host = null !== $host && \in_array($host, $matched, true) ? $host : $matched[0];

        return Activation::granted($record, $host);
    }
}

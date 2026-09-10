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

namespace VTInnovations\CentralizedNotificationSuite\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationChange;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRefused;

/**
 * Renews the stored record once the issuer's signed refresh deadline has passed.
 *
 * This is the half of revocation that does not depend on being reachable. A withdrawal pushed
 * to this installation can fail for reasons nobody can tell apart from the outside -- the site
 * is offline, behind a firewall, moved to a new address, or its administrator has deliberately
 * blocked inbound requests precisely so the withdrawal never lands. Push alone therefore makes
 * revocation best-effort, and "best-effort" is not a licence enforcement model.
 *
 * So the record carries its own expiry of trust: after license_refresh_required_at the
 * installation has to go and ask, and after license_grace_until the gate stops granting until
 * it has been told something newer. This job is what does the asking. It keeps running after
 * the grace cutoff, because that is exactly when a reinstatement needs to be able to arrive.
 *
 * A record with no signed lease is left alone -- records issued before the policy existed keep
 * working as they always did.
 */
#[AsCronJob('hourly')]
class RefreshActivationCron
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ActivationGate $gate,
        private readonly ActivationChange $change,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        $now = time();
        $record = $this->gate->storedRecord($now);

        // Nothing stored, nothing readable, or an issuer that set no lease on this record.
        if (null === $record || null === $record->refreshRequiredAt) {
            return;
        }

        if ($now < $record->refreshRequiredAt) {
            return;
        }

        try {
            // No replacement key: the stored one is reused, so the key never has to travel
            // back from anywhere it is not already held.
            $this->change->refresh();
        } catch (ActivationRefused $e) {
            // Category only, and not an error: a refusal here is the system working. Either
            // the issuer is briefly unreachable -- in which case the signed grace period is
            // what decides how long this installation may carry on -- or it answered with a
            // withdrawal, which has already been stored by the time this is reached.
            $this->logger->info('The scheduled activation refresh did not renew the record ('.$e->category().').');
        }
    }
}

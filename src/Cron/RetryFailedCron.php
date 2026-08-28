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
use VTInnovations\CentralizedNotificationSuite\Message\LogReplayer;
use VTInnovations\CentralizedNotificationSuite\Model\LogModel;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;
use VTInnovations\CentralizedNotificationSuite\CentralizedNotificationSuite;

/**
 * Re-attempts messages that failed to send. The common case this exists for is a mail
 * server that was briefly unreachable: without it, a notification lost to a 30-second
 * outage stays lost and nobody finds out until the customer complains.
 */
#[AsCronJob('hourly')]
class RetryFailedCron
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly CentralizedNotificationSuite $notifyCenter,
        private readonly LogReplayer $replayer,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly int $maxAttempts,
        private readonly ActivationGate $activation,
    ) {
    }

    public function __invoke(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Background work is gated too. A scheduled job runs with no administrator watching
        // and no browser session, so an unlicensed installation must not quietly keep
        // delivering through it.
        if (!$this->activation->current()->granted) {
            return;
        }

        $this->framework->initialize();

        $entries = LogModel::findRetryable($this->maxAttempts);

        if (!$entries) {
            return;
        }

        $retried = 0;
        $recovered = 0;

        foreach ($entries as $entry) {
            $prepared = $this->replayer->toPrepared($entry);

            if (!$prepared->isSendable()) {
                // Nothing a retry can fix (gateway deleted, body not stored). Stop
                // reconsidering it every hour by exhausting its attempts.
                $entry->attempts = $this->maxAttempts;
                $entry->error = $prepared->problem;
                $entry->save();

                continue;
            }

            ++$retried;

            if ($this->notifyCenter->deliver($prepared, CentralizedNotificationSuite::SOURCE_CRON)) {
                ++$recovered;
            }
        }

        if ($retried > 0) {
            $this->logger->info(\sprintf(
                'Centralized Notification Suite retry: re-attempted %d failed message(s), %d succeeded.',
                $retried,
                $recovered,
            ));
        }
    }
}

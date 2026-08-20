<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;
use VTInnovations\SimpleNotifyBundle\Message\LogReplayer;
use VTInnovations\SimpleNotifyBundle\Model\LogModel;
use VTInnovations\SimpleNotifyBundle\SimpleNotifyCenter;

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
        private readonly SimpleNotifyCenter $notifyCenter,
        private readonly LogReplayer $replayer,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly int $maxAttempts,
    ) {
    }

    public function __invoke(): void
    {
        if (!$this->enabled) {
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

            if ($this->notifyCenter->deliver($prepared, SimpleNotifyCenter::SOURCE_CRON)) {
                ++$recovered;
            }
        }

        if ($retried > 0) {
            $this->logger->info(\sprintf(
                'Simple Notify retry: re-attempted %d failed message(s), %d succeeded.',
                $retried,
                $recovered,
            ));
        }
    }
}

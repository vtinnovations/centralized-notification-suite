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
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Deletes send-log entries past the configured retention. A busy contact form produces a
 * row per submission, so an unbounded log is a slow-growing liability -- both for disk and
 * because it retains recipient addresses and message bodies indefinitely.
 */
#[AsCronJob('daily')]
class PruneLogCron
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly int $retentionDays,
    ) {
    }

    public function __invoke(): void
    {
        if ($this->retentionDays <= 0) {
            return;
        }

        $deleted = $this->prune($this->retentionDays);

        if ($deleted > 0) {
            $this->logger->info(\sprintf('Centralized Notification Suite: pruned %d send-log entr%s.', $deleted, 1 === $deleted ? 'y' : 'ies'));
        }
    }

    /**
     * @return int Number of deleted rows
     */
    public function prune(int $retentionDays): int
    {
        $threshold = time() - ($retentionDays * 86400);

        return (int) $this->connection->executeStatement(
            'DELETE FROM tl_notification_log WHERE tstamp < :threshold',
            ['threshold' => $threshold],
        );
    }
}

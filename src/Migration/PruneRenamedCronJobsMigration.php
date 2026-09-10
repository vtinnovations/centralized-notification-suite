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

namespace VTInnovations\CentralizedNotificationSuite\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Removes tl_cron_job rows left behind by a rename of this bundle.
 *
 * Contao keys its cron bookkeeping by service class name, so renaming the namespace orphans
 * the old rows: they point at classes that no longer exist and can never run again, while
 * Contao creates fresh rows for the new names on the next tick.
 *
 * Deleting rather than renaming is deliberate. The row records only when a job last ran, and
 * Contao recreates it on demand, so a delete costs at most one early run of a prune or retry
 * sweep -- both of which are idempotent. Carrying the old lastRun across would be guesswork
 * about which of two rows is authoritative.
 *
 * Kept separate from RenameToNotificationMigration instead of extending it: that migration
 * renames tables and rewrites user-group permissions, and a site that never ran it must not
 * be forced through that path just to tidy two cron rows. Both are idempotent, so whichever
 * runs first deletes and the other finds nothing.
 */
class PruneRenamedCronJobsMigration extends AbstractMigration
{
    /**
     * Namespace prefixes this bundle has shipped under. A future rename appends to this list.
     *
     * The current namespace is deliberately absent -- matching it would delete the rows that
     * are actually in use.
     */
    private const OLD_PREFIXES = [
        'VTInnovations\\SimpleNotifyBundle\\Cron\\',
        'VTInnovations\\ContaoNotificationBundle\\Cron\\',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Centralized Notification Suite: remove cron rows orphaned by the bundle rename';
    }

    public function shouldRun(): bool
    {
        return $this->countStale() > 0;
    }

    public function run(): MigrationResult
    {
        $removed = 0;

        foreach (self::OLD_PREFIXES as $prefix) {
            $removed += $this->connection->executeStatement(
                'DELETE FROM tl_cron_job WHERE LEFT(name, ?) = ?',
                [\strlen($prefix), $prefix],
            );
        }

        return $this->createResult(true, \sprintf('removed %d orphaned cron row(s)', $removed));
    }

    /**
     * Matched with LEFT() rather than LIKE: the name is a PHP namespace and a backslash is
     * LIKE's own escape character, so the pattern would silently match nothing.
     */
    private function countStale(): int
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_cron_job'])) {
            return 0;
        }

        $count = 0;

        foreach (self::OLD_PREFIXES as $prefix) {
            $count += (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM tl_cron_job WHERE LEFT(name, ?) = ?',
                [\strlen($prefix), $prefix],
            );
        }

        return $count;
    }
}

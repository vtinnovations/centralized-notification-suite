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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * Renames the tl_simple_* tables to tl_notification* after the bundle was renamed from
 * simple-notify-bundle to contao-notification-bundle.
 *
 * Renaming rather than recreating: the tables hold the notifications, messages and gateways
 * an editor configured, so a fresh schema would silently drop a working setup. The backend
 * module keys are stored inside tl_user_group.modules, which is why they are rewritten here
 * too -- otherwise a non-admin group would quietly lose access to the modules it was granted.
 */
class RenameToNotificationMigration extends AbstractMigration
{
    /**
     * Old table => new table.
     */
    private const TABLES = [
        'tl_simple_notification' => 'tl_notification',
        'tl_simple_message' => 'tl_notification_message',
        'tl_simple_gateway' => 'tl_notification_gateway',
        'tl_simple_template' => 'tl_notification_template',
        'tl_simple_log' => 'tl_notification_log',
    ];

    /**
     * Table => [old column => new column]. These live on Contao's own tables, so only the
     * column is renamed, never the table.
     */
    private const COLUMNS = [
        'tl_form' => ['simple_notify_notifications' => 'notification_ids'],
        'tl_module' => ['simple_notify_notifications' => 'notification_ids'],
    ];

    /**
     * Old backend module key => new key.
     */
    private const MODULES = [
        'simple_notify_gateway' => 'notification_gateway',
        'simple_notify_template' => 'notification_template',
        'simple_notify_log' => 'notification_log',
        'simple_notify' => 'notification',
    ];

    /**
     * Contao keys its cron bookkeeping by service class name, so the rename leaves the old
     * rows behind pointing at classes that no longer exist.
     *
     * Matched with LEFT() rather than LIKE: the name is a PHP namespace, and a backslash is
     * LIKE's own escape character, so the pattern would silently match nothing.
     */
    private const CRON_PREFIX_OLD = 'VTInnovations\\SimpleNotifyBundle\\';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Centralized Notification Suite: rename the tl_simple_* tables and module keys';
    }

    public function shouldRun(): bool
    {
        $schema = $this->connection->createSchemaManager();

        foreach (self::TABLES as $old => $new) {
            // Only rename when the new name is still free: if both exist, a rename would
            // fail and the choice of which to keep is not ours to make.
            if ($schema->tablesExist([$old]) && !$schema->tablesExist([$new])) {
                return true;
            }
        }

        foreach (self::COLUMNS as $table => $columns) {
            if (!$schema->tablesExist([$table])) {
                continue;
            }

            $existing = $schema->listTableColumns($table);

            foreach ($columns as $old => $new) {
                if (isset($existing[$old]) && !isset($existing[$new])) {
                    return true;
                }
            }
        }

        if ($this->hasStaleModules()) {
            return true;
        }

        return $this->countStaleCronRows() > 0;
    }

    public function run(): MigrationResult
    {
        $schema = $this->connection->createSchemaManager();
        $messages = [];

        foreach (self::TABLES as $old => $new) {
            if ($schema->tablesExist([$old]) && !$schema->tablesExist([$new])) {
                $this->connection->executeStatement(\sprintf(
                    'RENAME TABLE %s TO %s',
                    $this->connection->quoteIdentifier($old),
                    $this->connection->quoteIdentifier($new),
                ));

                $messages[] = \sprintf('%s -> %s', $old, $new);
            }
        }

        foreach (self::COLUMNS as $table => $columns) {
            if (!$schema->tablesExist([$table])) {
                continue;
            }

            $existing = $schema->listTableColumns($table);

            foreach ($columns as $old => $new) {
                if (!isset($existing[$old]) || isset($existing[$new])) {
                    continue;
                }

                // RENAME COLUMN keeps the definition, so the serialised ids survive as they are
                $this->connection->executeStatement(\sprintf(
                    'ALTER TABLE %s RENAME COLUMN %s TO %s',
                    $this->connection->quoteIdentifier($table),
                    $this->connection->quoteIdentifier($old),
                    $this->connection->quoteIdentifier($new),
                ));

                $messages[] = \sprintf('%s.%s -> %s', $table, $old, $new);
            }
        }

        if ($renamed = $this->renameModulePermissions()) {
            $messages[] = \sprintf('rewrote module permissions of %d user group(s)', $renamed);
        }

        if ($this->countStaleCronRows() > 0) {
            // Deleted rather than renamed: the row only records when a job last ran, and
            // Contao recreates it on the next run.
            $removed = $this->connection->executeStatement(
                'DELETE FROM tl_cron_job WHERE LEFT(name, ?) = ?',
                [\strlen(self::CRON_PREFIX_OLD), self::CRON_PREFIX_OLD],
            );

            $messages[] = \sprintf('removed %d stale cron row(s)', $removed);
        }

        return $this->createResult(true, $messages ? implode(', ', $messages) : null);
    }

    private function countStaleCronRows(): int
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_cron_job'])) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_cron_job WHERE LEFT(name, ?) = ?',
            [\strlen(self::CRON_PREFIX_OLD), self::CRON_PREFIX_OLD],
        );
    }

    private function hasStaleModules(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_user_group'])) {
            return false;
        }

        foreach ($this->connection->fetchFirstColumn('SELECT modules FROM tl_user_group') as $modules) {
            if (array_intersect(StringUtil::deserialize($modules, true), array_keys(self::MODULES))) {
                return true;
            }
        }

        return false;
    }

    private function renameModulePermissions(): int
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_user_group'])) {
            return 0;
        }

        $rows = $this->connection->fetchAllAssociative('SELECT id, modules FROM tl_user_group');
        $count = 0;

        foreach ($rows as $row) {
            $modules = StringUtil::deserialize($row['modules'], true);

            if (!array_intersect($modules, array_keys(self::MODULES))) {
                continue;
            }

            $updated = array_values(array_unique(array_map(
                static fn (string $module): string => self::MODULES[$module] ?? $module,
                $modules,
            )));

            $this->connection->update(
                'tl_user_group',
                ['modules' => serialize($updated)],
                ['id' => $row['id']],
            );

            ++$count;
        }

        return $count;
    }
}

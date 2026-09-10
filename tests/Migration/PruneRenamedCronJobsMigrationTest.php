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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Migration\PruneRenamedCronJobsMigration;

/**
 * The migration is pure SQL against one table, so the connection is doubled rather than a
 * database being booted -- matching the framework-free style of the rest of the suite.
 */
class PruneRenamedCronJobsMigrationTest extends TestCase
{
    public function testDoesNotRunWhenTheCronTableIsAbsent(): void
    {
        $migration = new PruneRenamedCronJobsMigration($this->connection(tableExists: false));

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunWhenNoStaleRowsRemain(): void
    {
        $migration = new PruneRenamedCronJobsMigration($this->connection(counts: [0, 0]));

        $this->assertFalse($migration->shouldRun());
    }

    public function testRunsWhenAnyHistoricalPrefixIsStillPresent(): void
    {
        $migration = new PruneRenamedCronJobsMigration($this->connection(counts: [0, 2]));

        $this->assertTrue($migration->shouldRun());
    }

    public function testDeletesEveryHistoricalPrefixAndReportsTheTotal(): void
    {
        $connection = $this->connection(counts: [1, 2], deletes: [1, 2]);
        $result = (new PruneRenamedCronJobsMigration($connection))->run();

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('removed 3 orphaned cron row(s)', $result->getMessage());
    }

    /**
     * The current namespace must never be targeted: matching it would delete the rows that
     * are actually in use, which is the one way this migration could do harm.
     */
    public function testNeverTargetsTheCurrentNamespace(): void
    {
        $prefixes = $this->prefixes();

        $this->assertNotEmpty($prefixes);

        foreach ($prefixes as $prefix) {
            $this->assertStringStartsNotWith(
                'VTInnovations\\CentralizedNotificationSuite\\',
                $prefix,
                \sprintf('Prefix "%s" would match live cron rows.', $prefix),
            );
        }
    }

    /**
     * Every prefix must end at a namespace separator, so a future bundle whose name merely
     * begins with the same characters cannot be caught by a LEFT() match.
     */
    public function testEveryPrefixIsNamespaceBounded(): void
    {
        foreach ($this->prefixes() as $prefix) {
            $this->assertStringEndsWith('\\', $prefix);
            $this->assertStringStartsWith('VTInnovations\\', $prefix);
        }
    }

    /**
     * @return list<string>
     */
    private function prefixes(): array
    {
        $property = new \ReflectionClassConstant(PruneRenamedCronJobsMigration::class, 'OLD_PREFIXES');

        return array_values((array) $property->getValue());
    }

    /**
     * @param list<int> $counts  one COUNT(*) result per prefix
     * @param list<int> $deletes one affected-row count per prefix
     */
    private function connection(bool $tableExists = true, array $counts = [], array $deletes = []): Connection
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn($tableExists);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(...($counts ?: [0]));

        if ($deletes) {
            $connection->method('executeStatement')->willReturnOnConsecutiveCalls(...$deletes);
        }

        return $connection;
    }
}

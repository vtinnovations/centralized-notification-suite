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
 * Sets type="form" on notifications that a form already triggers.
 *
 * tl_notification.type was introduced after the fact and defaults to "custom", but
 * the form generator's notification picker only lists "form" notifications. Without this,
 * upgrading would leave every existing form-triggered notification out of the picker -- and
 * an editor opening that form and saving it would silently detach the notification.
 *
 * Only rows still on the default are touched, so an explicit choice is never overwritten.
 */
class AssignFormNotificationTypeMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Centralized Notification Suite: mark form-triggered notifications as type "form"';
    }

    public function shouldRun(): bool
    {
        $schema = $this->connection->createSchemaManager();

        if (!$schema->tablesExist(['tl_notification', 'tl_form'])) {
            return false;
        }

        $columns = $schema->listTableColumns('tl_notification');

        if (!isset($columns['type'])) {
            return false;
        }

        if (!isset($schema->listTableColumns('tl_form')['notification_ids'])) {
            return false;
        }

        return [] !== $this->findIdsToUpdate();
    }

    public function run(): MigrationResult
    {
        $ids = $this->findIdsToUpdate();

        if ([] === $ids) {
            return $this->createResult(true, 'Nothing to update.');
        }

        $updated = $this->connection->executeStatement(
            'UPDATE tl_notification SET type = :type WHERE id IN (:ids)',
            ['type' => 'form', 'ids' => $ids],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER],
        );

        return $this->createResult(true, \sprintf('Marked %d notification(s) as form notifications.', $updated));
    }

    /**
     * @return list<int>
     */
    private function findIdsToUpdate(): array
    {
        $referenced = [];

        $values = $this->connection->fetchFirstColumn(
            "SELECT notification_ids FROM tl_form WHERE notification_ids IS NOT NULL AND notification_ids != ''",
        );

        foreach ($values as $value) {
            foreach (StringUtil::deserialize($value, true) as $id) {
                $referenced[(int) $id] = true;
            }
        }

        unset($referenced[0]);

        if (!$referenced) {
            return [];
        }

        return array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_notification WHERE id IN (:ids) AND type = :type',
            ['ids' => array_keys($referenced), 'type' => 'custom'],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER],
        ));
    }
}

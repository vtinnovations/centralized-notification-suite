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

namespace VTInnovations\CentralizedNotificationSuite\Store;

use Doctrine\DBAL\Connection;

/**
 * Remembers which server-initiated updates have already been handled.
 *
 * Two different things have to be told apart, and conflating them is the bug:
 *
 *   - the same request arriving twice, because a response was lost and the sender retried.
 *     That must succeed quietly and change nothing;
 *   - a different request reusing an id that was already used. That is a replay attempt and
 *     must be refused.
 *
 * The difference is decided by comparing a digest of the authenticated body, so "same" means
 * byte-identical rather than merely same-id.
 *
 * The table stores digests only. The body it summarises contains a full key and a signed
 * payload, and none of that belongs in a database row that shows up in a backup or a dump.
 */
class ExchangeJournal
{
    private const TABLE = 'tl_notification_exchange';

    /**
     * Kept well beyond any sane retry window so a late duplicate is still recognised, but
     * bounded so the table cannot grow forever.
     */
    private const RETENTION = 2592000;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Records this request as handled, or reports how it relates to one already seen.
     *
     * The insert is what decides. Relying on "select, then insert" would let two requests
     * arriving at once both find nothing and both apply -- the unique index on request_id
     * makes the database settle it instead.
     */
    public function claim(string $requestId, string $rawBody, string $nonce, int $version, int $now): ExchangeOutcome
    {
        $this->prune($now);

        $digest = hash('sha256', $rawBody);

        try {
            $this->connection->insert(self::TABLE, [
                'request_id' => $requestId,
                'body_digest' => $digest,
                'nonce_digest' => hash('sha256', $nonce),
                'applied_version' => $version,
                'seen' => $now,
            ]);

            return ExchangeOutcome::fresh();
        } catch (\Throwable) {
            // The unique index rejected it, so this id has been seen before.
        }

        $existing = $this->connection->fetchAssociative(
            'SELECT body_digest, applied_version FROM '.self::TABLE.' WHERE request_id = ?',
            [$requestId],
        );

        if (false === $existing) {
            // The row disappeared between the two statements -- pruned, or removed by hand.
            // Treating it as a conflict is the safe reading.
            return ExchangeOutcome::conflict();
        }

        return hash_equals((string) $existing['body_digest'], $digest)
            ? ExchangeOutcome::repeat((int) $existing['applied_version'])
            : ExchangeOutcome::conflict();
    }

    /**
     * Records the version that was actually applied, once the update has succeeded.
     */
    public function recordApplied(string $requestId, int $version): void
    {
        $this->connection->update(self::TABLE, ['applied_version' => $version], ['request_id' => $requestId]);
    }

    /**
     * Drops the claim when the update it was made for did not go through, so a genuine retry
     * is not mistaken for a duplicate of something that never happened.
     */
    public function release(string $requestId): void
    {
        $this->connection->delete(self::TABLE, ['request_id' => $requestId]);
    }

    private function prune(int $now): void
    {
        $this->connection->executeStatement(
            'DELETE FROM '.self::TABLE.' WHERE seen < ?',
            [$now - self::RETENTION],
        );
    }
}

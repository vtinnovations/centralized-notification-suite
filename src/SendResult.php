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

namespace VTInnovations\CentralizedNotificationSuite;

/**
 * Outcome of one CentralizedNotificationSuite::send() call. Because delivery failures are caught per
 * message rather than thrown, this is how a caller finds out what actually happened.
 */
class SendResult
{
    /** An attempt is in flight; the log row exists so a crash mid-send stays visible. */
    public const STATUS_PENDING = 'pending';

    /**
     * The gateway accepted the message. For e-mail that means it entered Contao's Messenger
     * queue -- the SMTP server has not seen it yet. MailerEventListener promotes the row to
     * "sent" or "failed" once the worker has actually delivered it.
     */
    public const STATUS_QUEUED = 'queued';

    /** The transport delivered the message. */
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /** Never attempted -- unpublished gateway, cancelled by a listener, ... */
    public const STATUS_SKIPPED = 'skipped';

    /** Statuses that mean the gateway took responsibility for the message. */
    public const ACCEPTED = [self::STATUS_SENT, self::STATUS_QUEUED];

    /** @var array<int, string> Status keyed by tl_notification_message.id */
    private array $statuses = [];

    /** @var array<int, string> Error text keyed by tl_notification_message.id */
    private array $errors = [];

    public function add(int $messageId, string $status, string|null $error = null): void
    {
        $this->statuses[$messageId] = $status;

        if (null !== $error) {
            $this->errors[$messageId] = $error;
        }
    }

    /**
     * True when every message was accepted by its gateway. Note that for queued mail this
     * is not proof of delivery -- check the send log for that.
     */
    public function isSuccessful(): bool
    {
        if ([] === $this->statuses) {
            return false;
        }

        foreach ($this->statuses as $status) {
            if (!\in_array($status, self::ACCEPTED, true)) {
                return false;
            }
        }

        return true;
    }

    public function hasFailures(): bool
    {
        return \in_array(self::STATUS_FAILED, $this->statuses, true);
    }

    public function countSent(): int
    {
        return \count(array_filter(
            $this->statuses,
            static fn (string $s): bool => \in_array($s, self::ACCEPTED, true),
        ));
    }

    /**
     * @return array<int, string>
     */
    public function getStatuses(): array
    {
        return $this->statuses;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Message id => whether the gateway accepted it.
     *
     * @return array<int, bool>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (string $s): bool => \in_array($s, self::ACCEPTED, true),
            $this->statuses,
        );
    }
}

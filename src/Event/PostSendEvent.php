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

namespace VTInnovations\CentralizedNotificationSuite\Event;

use Symfony\Contracts\EventDispatcher\Event;
use VTInnovations\CentralizedNotificationSuite\Message\RenderedMessage;
use VTInnovations\CentralizedNotificationSuite\SendResult;

/**
 * Dispatched after the gateway returned or threw. $throwable is null on success; on
 * failure it carries the original exception so listeners can react (alerting, metrics)
 * without the exception ever reaching the code that triggered the notification.
 */
class PostSendEvent extends Event
{
    /**
     * @param string               $status        A SendResult::STATUS_* value. Carried explicitly rather than
     *                                            derived from $successful, because "not sent" covers two very
     *                                            different outcomes: a delivery that failed and should be
     *                                            retried, and one that was deliberately skipped and must not be.
     * @param array<string, mixed> $gatewayConfig Raw tl_notification_gateway row
     */
    public function __construct(
        public readonly RenderedMessage $message,
        public readonly string $gatewayType,
        public readonly array $gatewayConfig,
        public readonly string $source,
        public readonly string $status,
        public readonly \Throwable|null $throwable = null,
    ) {
    }

    public function isSuccessful(): bool
    {
        return \in_array($this->status, SendResult::ACCEPTED, true);
    }

    public function wasSkipped(): bool
    {
        return SendResult::STATUS_SKIPPED === $this->status;
    }
}

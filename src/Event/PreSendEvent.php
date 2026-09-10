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

/**
 * Dispatched after a message is rendered and before the gateway sends it. Listeners may
 * rewrite the message (its content properties are mutable) or call cancel() to suppress
 * delivery -- a cancelled message is recorded as "skipped" rather than "failed".
 */
class PreSendEvent extends Event
{
    private bool $cancelled = false;

    private string|null $cancelReason = null;

    /**
     * @param array<string, mixed> $gatewayConfig Raw tl_notification_gateway row
     */
    public function __construct(
        public readonly RenderedMessage $message,
        public readonly string $gatewayType,
        public readonly array $gatewayConfig,
        public readonly string $source,
    ) {
    }

    public function cancel(string|null $reason = null): void
    {
        $this->cancelled = true;
        $this->cancelReason = $reason;
        $this->stopPropagation();
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function getCancelReason(): string|null
    {
        return $this->cancelReason;
    }
}

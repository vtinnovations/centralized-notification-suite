<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use VTInnovations\SimpleNotifyBundle\Message\RenderedMessage;

/**
 * Dispatched after the gateway returned or threw. $throwable is null on success; on
 * failure it carries the original exception so listeners can react (alerting, metrics)
 * without the exception ever reaching the code that triggered the notification.
 */
class PostSendEvent extends Event
{
    /**
     * @param array<string, mixed> $gatewayConfig Raw tl_simple_gateway row
     */
    public function __construct(
        public readonly RenderedMessage $message,
        public readonly string $gatewayType,
        public readonly array $gatewayConfig,
        public readonly string $source,
        public readonly bool $successful,
        public readonly \Throwable|null $throwable = null,
    ) {
    }
}

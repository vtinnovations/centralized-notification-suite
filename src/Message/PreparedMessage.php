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

namespace VTInnovations\CentralizedNotificationSuite\Message;

use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;

/**
 * A rendered message paired with the gateway that should deliver it. $problem is set when
 * the message cannot be sent at all -- a deleted or unpublished gateway, or a gateway type
 * whose bundle is no longer installed. Keeping that as data rather than an exception lets
 * the backend list and the send log show *why* nothing arrived.
 */
class PreparedMessage
{
    public function __construct(
        public readonly RenderedMessage $message,
        public readonly GatewayModel|null $gateway,
        public readonly string|null $problem = null,
    ) {
    }

    public function isSendable(): bool
    {
        return null === $this->problem && null !== $this->gateway;
    }

    public function getGatewayType(): string
    {
        return (string) ($this->gateway?->type ?? '');
    }
}

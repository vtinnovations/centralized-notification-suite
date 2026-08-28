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

namespace VTInnovations\CentralizedNotificationSuite\Gateway;

/**
 * Convenience base for gateways that need no backend configuration of their own.
 */
abstract class AbstractGateway implements GatewayInterface
{
    public function getConfigFields(): array
    {
        return [];
    }

    public function getPalette(): string
    {
        return '';
    }

    /**
     * Most transports deliver within send(); the e-mail gateway overrides this.
     */
    public function isAsynchronous(): bool
    {
        return false;
    }

    /**
     * Deliberately true: a gateway that has not thought about the question is treated as
     * addressing its recipients, so the backend keeps asking for them. A gateway whose
     * destination is its own configuration -- a file path, a webhook URL -- says so.
     */
    public function addressesRecipients(): bool
    {
        return true;
    }
}

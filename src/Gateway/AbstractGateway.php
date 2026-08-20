<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Gateway;

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
}

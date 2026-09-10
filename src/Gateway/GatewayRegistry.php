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

use VTInnovations\CentralizedNotificationSuite\Exception\NotificationException;

class GatewayRegistry
{
    /** @var array<string, GatewayInterface> */
    private array $gateways = [];

    /**
     * @param iterable<GatewayInterface> $gateways
     */
    public function __construct(iterable $gateways)
    {
        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->getName()] = $gateway;
        }

        ksort($this->gateways);
    }

    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    public function get(string $name): GatewayInterface
    {
        return $this->gateways[$name] ?? throw NotificationException::unknownGateway($name);
    }

    /**
     * @return array<string, GatewayInterface>
     */
    public function all(): array
    {
        return $this->gateways;
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->gateways);
    }
}

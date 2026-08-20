<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Gateway;

use VTInnovations\SimpleNotifyBundle\Exception\SimpleNotifyException;

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
        return $this->gateways[$name] ?? throw SimpleNotifyException::unknownGateway($name);
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

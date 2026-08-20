<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Gateway;

use VTInnovations\SimpleNotifyBundle\Message\RenderedMessage;

/**
 * A transport that can deliver a rendered message. Implementations are registered
 * automatically via the "simple_notify.gateway" tag (see config/services.yaml).
 *
 * A gateway also declares its own backend configuration: getConfigFields() returns DCA
 * field definitions that GatewayDcaListener merges into tl_simple_gateway, and
 * getPalette() the palette shown when this type is selected. That is what keeps one
 * gateway's settings from appearing in another's edit mask, and it means a third-party
 * gateway needs no DCA file of its own.
 */
interface GatewayInterface
{
    /**
     * Stored in tl_simple_gateway.type, so it must stay stable once released.
     */
    public function getName(): string;

    /**
     * DCA field definitions keyed by field name. Each definition needs an "sql" key so
     * contao:migrate creates the column. Prefix names distinctly enough to avoid
     * colliding with other gateways -- all types share the tl_simple_gateway table.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getConfigFields(): array;

    /**
     * Palette fragment for this gateway type, without the shared title/publish legends.
     * Return an empty string for a gateway that needs no configuration.
     */
    public function getPalette(): string;

    /**
     * Whether send() returning true means "handed off" rather than "delivered".
     *
     * The e-mail gateway is asynchronous because Contao routes mail through a Messenger
     * queue: the SMTP server is only contacted once a worker runs, so success is not known
     * yet. Saying so explicitly is what lets the send log distinguish "queued" from
     * "delivered" instead of claiming delivery it cannot vouch for.
     */
    public function isAsynchronous(): bool;

    /**
     * Deliver the message. Throwing is allowed and expected on failure: SimpleNotifyCenter
     * catches it, records it in the send log and moves on to the next message, so a broken
     * transport never surfaces as an error to the visitor who triggered the notification.
     *
     * @param array<string, mixed> $gatewayConfig Raw tl_simple_gateway row
     */
    public function send(RenderedMessage $message, array $gatewayConfig): bool;
}

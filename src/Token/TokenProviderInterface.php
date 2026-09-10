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

namespace VTInnovations\CentralizedNotificationSuite\Token;

/**
 * Declares which ##tokens## a kind of notification offers, so the backend can show an
 * accurate list instead of leaving editors to guess names and find out by sending.
 *
 * Providers are collected automatically via the "centralized_notification_suite.token_provider" tag.
 */
interface TokenProviderInterface
{
    /**
     * Applies to every notification type, whatever its trigger.
     */
    public const TYPE_ANY = '*';

    /**
     * A tl_notification.type value, or TYPE_ANY.
     */
    public function getType(): string;

    /**
     * Token name (without the ## markers) => what it contains. Names may use a "*" suffix
     * to document a family of tokens whose exact names depend on the data, e.g. "form field
     * names" -- those are shown in the reference but cannot be enumerated up front.
     *
     * @return array<string, string>
     */
    public function getDefinitions(): array;

    /**
     * Values this provider contributes to every send of a matching notification. Return an
     * empty array for a provider that only documents tokens supplied by its trigger.
     *
     * @return array<string, string>
     */
    public function getValues(): array;
}

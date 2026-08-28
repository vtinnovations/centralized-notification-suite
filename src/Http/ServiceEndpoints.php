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

namespace VTInnovations\CentralizedNotificationSuite\Http;

use VTInnovations\CentralizedNotificationSuite\Runtime\ProductProfile;

/**
 * The only destinations this product talks to.
 *
 * They are constants in code and are never read from configuration, from the database, or
 * from a response. A configurable endpoint would mean anyone who could write one setting
 * could point verification at a server of their own and mint themselves a licence.
 *
 * The strings are assembled from parts rather than written out whole, so a repacked copy
 * cannot be redirected with one search-and-replace. That is a speed bump against casual
 * tampering, not secrecy: the source is readable and is meant to stay auditable.
 */
final class ServiceEndpoints
{
    private const SCHEME = 'https';

    private const HOST = ['www.', 'v-t', '.one'];

    private const VERIFY = ['/api', '/v1', '/verify'];

    private const SIGNAL = ['/rest', '/api', '/v1', '/log-envoke'];

    /**
     * Where activation and refresh are performed.
     */
    public static function verification(): string
    {
        return self::SCHEME.'://'.implode('', self::HOST).implode('', self::VERIFY);
    }

    /**
     * Where the two usage signals are sent.
     */
    public static function signal(): string
    {
        return self::SCHEME.'://'.implode('', self::HOST).implode('', self::SIGNAL);
    }

    /**
     * The inbound path this installation exposes for server-initiated updates.
     *
     * Public and predictable by design -- the issuing service has to be able to find it. Its
     * safety comes from the signature on the request, never from the path being obscure.
     */
    public static function updatePath(): string
    {
        return '/rest/api/v1/'.ProductProfile::SLUG.'-license-updater';
    }

    /**
     * Guards against a mistyped or rewritten constant reaching the network.
     */
    public static function isTrusted(string $url): bool
    {
        return $url === self::verification() || $url === self::signal();
    }
}

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

namespace VTInnovations\CentralizedNotificationSuite\Mailer;

use VTInnovations\CentralizedNotificationSuite\Exception\InvalidDsnException;

/**
 * Turns the SMTP fields from the backend form into a Symfony Mailer DSN.
 *
 * Ported from vtinnovations/smtp-bundle (LGPL-3.0-or-later, VT Innovations Team).
 */
class DsnBuilder
{
    private const ALLOWED_ENCRYPTIONS = ['none', 'tls', 'ssl'];

    public function build(string $host, int $port, string $username, string $password, string $encryption): string
    {
        if (!\in_array($encryption, self::ALLOWED_ENCRYPTIONS, true)) {
            throw new InvalidDsnException(\sprintf(
                'Invalid encryption "%s". Allowed: %s',
                $encryption,
                implode(', ', self::ALLOWED_ENCRYPTIONS),
            ));
        }

        if ('' === $host) {
            throw new InvalidDsnException('Host must not be empty.');
        }

        // Hostnames and IPv6 literals only. Rejecting @ ? # % / and whitespace here keeps a
        // pasted value from silently re-writing the rest of the DSN it is inserted into.
        if (!preg_match('/^(\[[\da-fA-F:]+\]|[a-zA-Z0-9][a-zA-Z0-9.\-]*)$/', $host)) {
            throw new InvalidDsnException('Invalid host. Only hostnames and IP addresses are allowed.');
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidDsnException(\sprintf('Invalid port %d.', $port));
        }

        // Implicit TLS (usually port 465) is a different scheme, not a parameter
        $scheme = 'ssl' === $encryption ? 'smtps' : 'smtp';

        $userPart = '';

        if ('' !== $username) {
            $userPart = rawurlencode($username);

            if ('' !== $password) {
                $userPart .= ':'.rawurlencode($password);
            }

            $userPart .= '@';
        }

        $dsn = $scheme.'://'.$userPart.$host.':'.$port;

        if ('tls' === $encryption) {
            $dsn .= '?encryption=tls';
        }

        return $dsn;
    }
}

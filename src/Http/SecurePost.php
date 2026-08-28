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

/**
 * A deliberately narrow HTTPS POST, used for every outbound call this product makes.
 *
 * cURL is driven directly rather than through a general-purpose client because the defaults
 * that matter here are the ones a convenience layer tends to relax. Each of these is a
 * decision, not boilerplate:
 *
 *   - redirects are refused, so a 302 cannot move the request to another host;
 *   - only https is permitted, so a redirect or malformed constant cannot downgrade it;
 *   - peer and hostname verification stay on, because the endpoint's identity is the whole
 *     point of pinning it;
 *   - the response is capped while it is still arriving, so a hostile or broken server
 *     cannot exhaust memory;
 *   - connect and total timeouts are short, because an administrator is waiting and a
 *     visitor's page must never hang behind this.
 *
 * Nothing here inspects or logs the body it sends.
 */
class SecurePost
{
    /**
     * Enough for a signed package with room to spare; far below anything that would hurt.
     */
    private const MAX_RESPONSE = 262144;

    private const CONNECT_TIMEOUT = 5;

    private const TOTAL_TIMEOUT = 15;

    /**
     * @param array<string, string> $headers
     *
     * @throws TransportFailed
     */
    public function send(string $url, string $body, array $headers = [], int $timeout = self::TOTAL_TIMEOUT): Reply
    {
        // A last check that the caller is using one of the pinned constants and not a value
        // that reached it from configuration or a response.
        if (!ServiceEndpoints::isTrusted($url)) {
            throw new TransportFailed('untrusted_destination');
        }

        $handle = curl_init();

        if (false === $handle) {
            throw new TransportFailed('transport_unavailable');
        }

        $received = '';
        $overflowed = false;

        $lines = ['Content-Type: application/json', 'Accept: application/json'];

        foreach ($headers as $name => $value) {
            $lines[] = $name.': '.$value;
        }

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => max(1, $timeout),
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
            // Abort mid-stream rather than buffering whatever arrives and checking after
            CURLOPT_WRITEFUNCTION => static function ($_, string $chunk) use (&$received, &$overflowed): int {
                if (\strlen($received) + \strlen($chunk) > self::MAX_RESPONSE) {
                    $overflowed = true;

                    return -1;
                }

                $received .= $chunk;

                return \strlen($chunk);
            },
        ]);

        if (\defined('CURLOPT_PROTOCOLS_STR')) {
            curl_setopt($handle, CURLOPT_PROTOCOLS_STR, 'https');
            curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS_STR, 'https');
        } elseif (\defined('CURLPROTO_HTTPS')) {
            curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
        }

        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);

        if ($overflowed) {
            throw new TransportFailed('response_too_large');
        }

        if (false === $ok && 0 === $status) {
            // The cURL message is not carried: it can contain internal hostnames and paths,
            // and none of it is safe for a log or a screen.
            throw new TransportFailed('unreachable');
        }

        return new Reply($status, $type, $received);
    }
}

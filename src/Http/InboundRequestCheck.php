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

use Symfony\Component\HttpFoundation\Request;
use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;

/**
 * Authenticates a server-initiated update request.
 *
 * This endpoint is reachable without logging in -- it has to be, the issuing service is not a
 * browser and holds no session. So none of the usual signals mean anything here: an Origin
 * header, a Referer, a User-Agent and a source IP are all attacker-supplied. The only thing
 * that establishes who sent this request is the signature over it.
 *
 * The signed message is fixed by the wire format and is built from six lines joined with a
 * single newline and no trailing one:
 *
 *   METHOD \n path \n request id \n timestamp \n nonce \n sha256(raw body)
 *
 * The key id header selects which key to check against but is deliberately not one of those
 * lines. That is safe only because the key is looked up in the pinned ring: an attacker may
 * name any key id they like, and every id that is not pinned resolves to nothing.
 *
 * The body hash is taken over the raw bytes, before parsing. Hashing a re-encoded copy would
 * let two different byte strings share one signature.
 */
class InboundRequestCheck
{
    /**
     * How far apart the two clocks may be. Narrow enough that a captured request is useless
     * within minutes, wide enough to survive ordinary clock drift.
     */
    private const SKEW = 300;

    public function __construct(private readonly IssuerKeyring $keys)
    {
    }

    /**
     * @throws InboundRejected
     */
    public function verify(Request $request, string $rawBody, int $now): void
    {
        if (!$this->keys->isUsable($now)) {
            throw new InboundRejected('signing_key_store_empty');
        }

        $requestId = self::header($request, 'X-VT-Request-ID');
        $timestamp = self::header($request, 'X-VT-Timestamp');
        $nonce = self::header($request, 'X-VT-Nonce');
        $keyId = self::header($request, 'X-VT-Key-ID');
        $signature = self::header($request, 'X-VT-Signature');

        if (!ctype_digit($timestamp)) {
            throw new InboundRejected('bad_timestamp');
        }

        $sent = (int) $timestamp;

        // Symmetric window: a request from the future is as suspicious as a stale one.
        if (abs($now - $sent) > self::SKEW) {
            throw new InboundRejected('stale_timestamp');
        }

        $key = $this->keys->find($keyId, $now);

        if (null === $key) {
            throw new InboundRejected('unknown_signing_key');
        }

        $raw = base64_decode($signature, true);

        if (false === $raw || SODIUM_CRYPTO_SIGN_BYTES !== \strlen($raw)) {
            throw new InboundRejected('malformed_signature');
        }

        $message = implode("\n", [
            strtoupper($request->getMethod()),
            // The path as served, so a signature made for one endpoint cannot be replayed
            // against another on the same host.
            $request->getPathInfo(),
            $requestId,
            $timestamp,
            $nonce,
            hash('sha256', $rawBody),
        ]);

        try {
            $holds = sodium_crypto_sign_verify_detached($raw, $message, $key);
        } catch (\SodiumException) {
            throw new InboundRejected('malformed_signature');
        }

        if (!$holds) {
            throw new InboundRejected('signature_invalid');
        }
    }

    /**
     * The headers that are also repeated inside the body must agree exactly.
     *
     * Only the header copies are signed, so a mismatch means someone edited the body after it
     * was signed and is hoping the two halves are read by different code.
     *
     * @throws InboundRejected
     */
    public function requireAgreement(Request $request, \stdClass $body): void
    {
        foreach (['X-VT-Request-ID' => 'request_id', 'X-VT-Timestamp' => 'timestamp', 'X-VT-Nonce' => 'nonce'] as $header => $field) {
            $inBody = $body->$field ?? null;
            $inBody = \is_int($inBody) ? (string) $inBody : $inBody;

            if (!\is_string($inBody) || !hash_equals(self::header($request, $header), $inBody)) {
                throw new InboundRejected('header_body_mismatch');
            }
        }
    }

    private static function header(Request $request, string $name): string
    {
        return trim((string) $request->headers->get($name, ''));
    }
}

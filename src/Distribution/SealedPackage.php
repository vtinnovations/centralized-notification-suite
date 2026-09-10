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

namespace VTInnovations\CentralizedNotificationSuite\Distribution;

/**
 * Opens a signed package delivered by the issuing service.
 *
 * Three checks have to pass, and the security property is that none of them may be skipped:
 *
 *   1. the integrity envelope's own signature, which is what makes the MD5 it carries mean
 *      anything at all;
 *   2. that MD5, compared in constant time against the exact decoded bytes -- an exact-byte
 *      tripwire, never a proof of authenticity;
 *   3. the document's own detached signature, over its canonical form.
 *
 * Dropping (1) is the classic mistake: it leaves MD5 as the only thing standing between an
 * edited document and acceptance, and anyone can recompute an MD5. The envelope is verified
 * first because failing early on the cheapest decisive check gives the clearest diagnosis,
 * but the order is a diagnostic preference -- swapping (1) and (2) would still reject the
 * same packets, since both must hold. Every decision that actually matters rests on the
 * Ed25519 signatures.
 *
 * The decoded bytes are returned verbatim and are what the caller must store. Re-serialising
 * a parsed document would change the bytes and break the digest and signature on the next
 * read, so nothing here ever hands back a re-encoded copy.
 */
final class SealedPackage
{
    /**
     * @param string    $bytes    the exact decoded licence bytes, to be stored verbatim
     * @param \stdClass $document the parsed form of those bytes, for validation only
     * @param \stdClass $envelope the authenticated integrity envelope, stored alongside
     */
    private function __construct(
        public readonly string $bytes,
        public readonly \stdClass $document,
        public readonly \stdClass $envelope,
    ) {
    }

    public static function open(\stdClass $response, IssuerKeyring $keys, int $now): self
    {
        if (!$keys->isUsable($now)) {
            // Diagnostic category only. It must never become a reason to skip verification.
            throw new UnverifiedPackage('signing_key_store_empty');
        }

        $envelope = $response->integrity ?? null;

        if (!$envelope instanceof \stdClass) {
            throw new UnverifiedPackage('missing_integrity_envelope');
        }

        $bytes = self::decodePayload($response->license_payload_b64 ?? null);

        self::verifyEnvelope($envelope, $keys, $now);
        self::verifyDigest($envelope, $bytes);

        $document = CanonicalForm::decode($bytes);

        self::verifyDocument($document, $keys, $now);

        return new self($bytes, $document, $envelope);
    }

    /**
     * Strict decoding: a payload carrying whitespace or non-alphabet characters is rejected
     * rather than silently repaired, because the bytes that come out are the bytes whose
     * digest and signature were checked.
     */
    private static function decodePayload(mixed $payload): string
    {
        if (!\is_string($payload) || '' === $payload) {
            throw new UnverifiedPackage('missing_payload');
        }

        $bytes = base64_decode($payload, true);

        if (false === $bytes || '' === $bytes) {
            throw new UnverifiedPackage('malformed_payload');
        }

        return $bytes;
    }

    /**
     * The envelope names its key id and algorithm, so both are resolved exactly. A response
     * may not nominate an algorithm this build does not accept.
     */
    private static function verifyEnvelope(\stdClass $envelope, IssuerKeyring $keys, int $now): void
    {
        $keyId = $envelope->key_id ?? null;
        $algorithm = $envelope->signature_algorithm ?? null;

        if (!\is_string($keyId) || !\is_string($algorithm)) {
            throw new UnverifiedPackage('missing_envelope_key_reference');
        }

        if (!hash_equals(IssuerKeyring::ALGORITHM, $algorithm)) {
            throw new UnverifiedPackage('unsupported_signature_algorithm');
        }

        $key = $keys->find($keyId, $now);

        if (null === $key) {
            throw new UnverifiedPackage('unknown_signing_key');
        }

        if (!self::signatureHolds($envelope, [$key])) {
            throw new UnverifiedPackage('envelope_signature_invalid');
        }
    }

    /**
     * Constant-time comparison: a byte-by-byte early exit would leak how much of a forged
     * digest was correct.
     */
    private static function verifyDigest(\stdClass $envelope, string $bytes): void
    {
        $expected = $envelope->license_md5 ?? null;

        if (!\is_string($expected) || '' === $expected) {
            throw new UnverifiedPackage('missing_digest');
        }

        if (!hash_equals(strtolower($expected), md5($bytes))) {
            throw new UnverifiedPackage('digest_mismatch');
        }
    }

    /**
     * The document names no key, so its signature is tried against every currently usable
     * key. That is the issuer's documented behaviour and is what makes key rotation possible
     * without invalidating documents signed by the outgoing key.
     */
    private static function verifyDocument(\stdClass $document, IssuerKeyring $keys, int $now): void
    {
        if (!self::signatureHolds($document, $keys->usable($now))) {
            throw new UnverifiedPackage('document_signature_invalid');
        }
    }

    /**
     * @param list<string> $candidates
     */
    private static function signatureHolds(\stdClass $document, array $candidates): bool
    {
        $signature = $document->signature ?? null;

        if (!\is_string($signature) || '' === $signature) {
            return false;
        }

        $raw = base64_decode($signature, true);

        if (false === $raw || SODIUM_CRYPTO_SIGN_BYTES !== \strlen($raw)) {
            return false;
        }

        $message = CanonicalForm::bytes($document);
        $holds = false;

        foreach ($candidates as $key) {
            // No early break: every candidate is tried so the work does not depend on which
            // key matched.
            try {
                $holds = sodium_crypto_sign_verify_detached($raw, $message, $key) || $holds;
            } catch (\SodiumException) {
                // A malformed key or signature is a failed verification, never an exception
                // that could be caught further up and mistaken for an unrelated fault.
                continue;
            }
        }

        return $holds;
    }
}

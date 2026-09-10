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
 * The pinned public verification keys of the issuing service.
 *
 * These are public keys, not secrets -- but their authenticity is what the whole trust chain
 * rests on, so they are pinned in code and never taken from configuration, from a response,
 * or from anywhere an attacker who can reach the site could influence. A key id names a key;
 * it is never treated as one.
 *
 * The material is assembled at runtime rather than written as one readable literal, and the
 * result is checked against its published SHA-256 fingerprint before use. The split is not
 * secrecy -- anyone may read the source -- it removes the one-line grep-and-replace that
 * would otherwise let a repacked copy substitute an attacker's key.
 */
final class IssuerKeyring
{
    /**
     * Ed25519 is the only algorithm this deployment profile accepts. An allowlist rather than
     * a lookup: an unknown or downgraded algorithm identifier must fail, never fall through
     * to "no verification".
     */
    public const ALGORITHM = 'ed25519';

    /**
     * The pinned ring: key id => [material fragments, published fingerprint prefix, usable
     * from, usable until].
     *
     * The material is held in fragments and reassembled at runtime, and the published
     * fingerprint is checked before the key is used, so a mangled or repacked build fails
     * closed rather than rejecting every genuine response with a confusing signature error.
     * The active profile declares no retirement, recorded as null rather than a far-future
     * guess.
     *
     * @var array<string, array{0: list<string>, 1: string, 2: int, 3: int|null}>
     */
    private const PINNED = [
        'vtone-2026a' => [
            ['qllgm+66FUVBFJ3O', '68ICFG8b37dR+9jM', 'fr1+4/pSygE='],
            'edcd614e70c59ce0',
            0,
            null,
        ],
    ];

    /**
     * @var array<string, string>|null
     */
    private array|null $resolved = null;

    /**
     * @param array<string, array{0: list<string>, 1: string, 2: int, 3: int|null}> $ring
     *        Defaults to the pinned production ring. The container registers this service
     *        with no arguments, so production always uses that default; the parameter exists
     *        so the verification path itself can be exercised against a purpose-generated
     *        key. Supplying a ring is not a bypass: every entry still has to satisfy the
     *        length and fingerprint checks in all(), and reaching this constructor already
     *        requires the ability to run arbitrary code in the application.
     */
    public function __construct(private readonly array $ring = self::PINNED)
    {
    }

    /**
     * The verification key for one advertised id, or null when this deployment does not pin
     * it. Callers must treat null as a hard failure -- there is no unsigned path.
     */
    public function find(string $keyId, int $now): string|null
    {
        foreach ($this->all() as $id => $material) {
            if (!hash_equals($id, $keyId)) {
                continue;
            }

            [, , $from, $until] = $this->ring[$id];

            if ($now < $from || (null !== $until && $now >= $until)) {
                return null;
            }

            return $material;
        }

        return null;
    }

    /**
     * Every currently usable key.
     *
     * The licence document names no key id, so its detached signature is tried against each
     * of these in turn until one verifies. The integrity envelope does name one, and must use
     * find() so a response cannot select a key the envelope was not signed with.
     *
     * @return list<string>
     */
    public function usable(int $now): array
    {
        $keys = [];

        foreach ($this->all() as $id => $material) {
            [, , $from, $until] = $this->ring[$id];

            if ($now >= $from && (null === $until || $now < $until)) {
                $keys[] = $material;
            }
        }

        return $keys;
    }

    /**
     * True when this build carries at least one structurally valid pinned key.
     *
     * A distributable artefact whose ring is empty can never verify a genuine response, so
     * this is asserted at build time as well as before every verification.
     */
    public function isUsable(int $now): bool
    {
        return [] !== $this->usable($now);
    }

    /**
     * Assembles and validates the ring once.
     *
     * A fragment set that does not decode to the exact key length, or whose fingerprint does
     * not match the published value, is dropped rather than used: a partially corrupted build
     * must not verify anything.
     *
     * @return array<string, string>
     */
    private function all(): array
    {
        if (null !== $this->resolved) {
            return $this->resolved;
        }

        $ring = [];

        foreach ($this->ring as $id => [$fragments, $fingerprint]) {
            $material = base64_decode(implode('', $fragments), true);

            if (false === $material || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== \strlen($material)) {
                continue;
            }

            if (!hash_equals($fingerprint, substr(hash('sha256', $material), 0, \strlen($fingerprint)))) {
                continue;
            }

            $ring[$id] = $material;
        }

        return $this->resolved = $ring;
    }
}

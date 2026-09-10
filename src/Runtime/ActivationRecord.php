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

namespace VTInnovations\CentralizedNotificationSuite\Runtime;

/**
 * An authenticated record, checked against what this product is and where it may run.
 *
 * Reaching this class means the signatures and the exact-byte digest already held, so the
 * document is genuine. Genuine is not the same as applicable: a real record issued for
 * another product, another tier, another host or a future schema must still be refused. That
 * second half is what happens here.
 *
 * Every rejection is a fixed category string. None of them is recoverable by retrying, and
 * none of them may be turned into a partial entitlement -- an unusable record leaves the
 * product exactly as it behaves with no record at all.
 */
final class ActivationRecord
{
    /**
     * @param list<string> $hosts    the signed set of hostnames this record authorises
     * @param list<string> $features
     */
    private function __construct(
        public readonly string $key,
        public readonly string $status,
        public readonly string $host,
        public readonly array $hosts,
        public readonly int $maxHosts,
        public readonly string $package,
        public readonly array $features,
        public readonly int $version,
        public readonly int $issuedAt,
        public readonly int $startsAt,
        public readonly int|null $expiresAt,
        public readonly bool $lifetime,
        public readonly int|null $refreshRequiredAt,
        public readonly int|null $graceUntil,
    ) {
    }

    /**
     * Whether this record grants anything.
     *
     * The one question that separates the two halves of this class: everything above it is
     * about whether the document is genuine and about this product, and a negative record
     * passes all of that. What it does not do is grant.
     */
    public function positive(): bool
    {
        return ProductProfile::STATUS_VALID === $this->status;
    }

    /**
     * @throws RecordNotApplicable
     */
    public static function fromDocument(\stdClass $d, int $now): self
    {
        self::require(ProductProfile::SCHEMA === ($d->schema_version ?? null), 'schema_unsupported');
        self::require(ProductProfile::NAME === ($d->project ?? null), 'wrong_product');
        self::require(ProductProfile::SLUG === ($d->project_slug ?? null), 'wrong_product');

        // The status decides how strictly the rest is read. It is an entitlement outcome, not
        // a verdict on authenticity: refusing to parse a revoked record would mean the issuer
        // could grant an entitlement but never withdraw one, which is the wrong way round for
        // anything to fail.
        $status = $d->validation_status ?? null;
        self::require(\is_string($status) && \in_array($status, ProductProfile::KNOWN_STATUSES, true), 'unknown_status');

        $positive = ProductProfile::STATUS_VALID === $status;

        $key = $d->license_key ?? null;
        self::require(\is_string($key) && '' !== trim($key), 'missing_key');

        $version = $d->license_version ?? null;
        self::require(\is_int($version) && $version >= 1, 'bad_version');

        [$host, $hosts, $maxHosts] = self::hosts($d, $positive);
        [$issuedAt, $startsAt, $expiresAt, $lifetime] = self::validity($d, $now, $positive);

        $package = $d->license_package ?? null;
        self::require(\is_string($package), 'bad_package');

        // The tier contract, and only for a record that grants something. A withdrawal that
        // named a tier this build is not sold under would otherwise be refused and leave the
        // entitlement in place -- a tier mismatch must never be a reason to stay licensed.
        if ($positive) {
            self::require(\in_array($package, ProductProfile::ACCEPTED_PACKAGES, true), 'package_not_accepted');

            if (ProductProfile::REQUIRES_LIFETIME) {
                self::require($lifetime, 'lifetime_required');
                self::require(null === $expiresAt, 'lifetime_required');
            }
        }

        [$refreshRequiredAt, $graceUntil] = self::lease($d);

        return new self(
            key: $key,
            status: $status,
            host: $host,
            hosts: $hosts,
            maxHosts: $maxHosts,
            package: $package,
            features: self::features($d),
            version: $version,
            issuedAt: $issuedAt,
            startsAt: $startsAt,
            expiresAt: $expiresAt,
            lifetime: $lifetime,
            refreshRequiredAt: $refreshRequiredAt,
            graceUntil: $graceUntil,
        );
    }

    /**
     * True when this record authorises the given hostname.
     *
     * Exact membership of the signed set. There is no parent, child, sibling, apex or "www"
     * relationship, and maxHosts is not consulted -- a large allowance is a number the issuer
     * reports, never a wildcard.
     */
    public function authorises(string $host): bool
    {
        return \in_array($host, $this->hosts, true);
    }

    /**
     * @param bool $positive whether the record grants anything
     *
     * @return array{0: string, 1: list<string>, 2: int}
     */
    private static function hosts(\stdClass $d, bool $positive): array
    {
        $signed = $d->license_domains ?? null;
        self::require(\is_array($signed) && [] !== $signed, 'missing_host_set');
        self::require(array_is_list($signed), 'malformed_host_set');

        $hosts = [];

        foreach ($signed as $entry) {
            self::require(\is_string($entry), 'malformed_host_set');

            // Compared against its own normalised form rather than normalised in place: the
            // signature covers these exact strings, so a list that needs repairing is one
            // this client must refuse, not silently rewrite.
            $normal = InstallationHosts::normalise($entry);
            self::require(null !== $normal && $normal === $entry, 'malformed_host_set');

            $hosts[] = $entry;
        }

        $sorted = $hosts;
        sort($sorted, SORT_STRING);
        self::require($sorted === $hosts, 'host_set_not_canonical');
        self::require(\count(array_unique($hosts)) === \count($hosts), 'host_set_not_canonical');

        $host = $d->license_domain ?? null;
        self::require(\is_string($host), 'missing_host');
        self::require(InstallationHosts::normalise($host) === $host, 'malformed_host');

        // Membership is required of a record that grants something, and deliberately not of
        // one that withdraws. When a licence moves from A to B, A's withdrawal carries the new
        // authorised set -- which no longer contains A. Insisting on membership here would
        // make exactly the packet that ends A's entitlement the one packet A refuses.
        if ($positive) {
            self::require(\in_array($host, $hosts, true), 'host_not_in_set');
        }

        $max = $d->license_max_domains ?? null;
        self::require(\is_int($max) && $max > 0, 'bad_host_allowance');

        // Deliberately no check that count(hosts) <= max. The issuer lowers an allowance
        // without unbinding existing hosts, and enforcing it here would take working
        // installations dark for a change made on the server.

        return [$host, $hosts, $max];
    }

    /**
     * @param bool $positive whether the record grants anything
     *
     * @return array{0: int, 1: int, 2: int|null, 3: bool}
     */
    private static function validity(\stdClass $d, int $now, bool $positive): array
    {
        $issuedAt = $d->license_issued_at ?? null;
        $startsAt = $d->license_starts_at ?? null;
        $lifetime = $d->license_lifetime ?? null;
        $expiresAt = $d->license_expires_at ?? null;

        self::require(\is_int($issuedAt) && \is_int($startsAt), 'bad_dates');
        self::require(\is_bool($lifetime), 'bad_dates');
        self::require(null === $expiresAt || \is_int($expiresAt), 'bad_dates');

        // A perpetual record has no expiry, and a temporary one must say when it ends;
        // "no expiry" must never be readable as "never expires" by accident.
        self::require($lifetime ? null === $expiresAt : \is_int($expiresAt), 'expiry_contradicts_lifetime');

        if (\is_int($expiresAt)) {
            self::require($expiresAt > $startsAt, 'bad_dates');
        }

        // The clock only decides whether a record that claims to grant something still does.
        // A record that already says "expired" or "revoked" describes the past by definition,
        // so measuring it against now would reject the very statement it exists to make.
        if ($positive) {
            if (\is_int($expiresAt)) {
                self::require($now < $expiresAt, 'expired');
            }

            self::require($now >= $startsAt, 'not_yet_valid');
        }

        return [$issuedAt, $startsAt, $expiresAt, $lifetime];
    }

    /**
     * The signed online-refresh policy, if the issuer set one.
     *
     * Push delivery cannot be relied on to withdraw an entitlement: an installation may be
     * offline, behind a firewall, or deliberately blocking inbound requests, and none of those
     * are distinguishable from a delivery that simply has not arrived yet. These two fields
     * are what make revocation eventually certain anyway -- after the refresh deadline the
     * installation must go and ask, and after the grace cutoff it stops granting until it has.
     *
     * Both are optional: records issued before the policy existed carry neither and keep
     * working exactly as they did. They are signed, so an installation cannot extend its own
     * lease by editing a local file.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private static function lease(\stdClass $d): array
    {
        $refreshRequiredAt = $d->license_refresh_required_at ?? null;
        $graceUntil = $d->license_grace_until ?? null;

        self::require(null === $refreshRequiredAt || \is_int($refreshRequiredAt), 'bad_lease');
        self::require(null === $graceUntil || \is_int($graceUntil), 'bad_lease');

        // A grace cutoff before the deadline it extends would mean the record was already
        // beyond grace the moment it was issued.
        if (\is_int($refreshRequiredAt) && \is_int($graceUntil)) {
            self::require($graceUntil >= $refreshRequiredAt, 'bad_lease');
        }

        // A deadline with no cutoff is a deadline that never bites; treat the deadline itself
        // as the cutoff rather than inventing a grace period the issuer did not sign.
        if (\is_int($refreshRequiredAt) && null === $graceUntil) {
            $graceUntil = $refreshRequiredAt;
        }

        return [$refreshRequiredAt, $graceUntil];
    }

    /**
     * @return list<string>
     */
    private static function features(\stdClass $d): array
    {
        $features = $d->license_features ?? [];
        self::require(\is_array($features) && array_is_list($features), 'bad_features');

        foreach ($features as $feature) {
            self::require(\is_string($feature), 'bad_features');
        }

        return $features;
    }

    private static function require(bool $condition, string $category): void
    {
        if (!$condition) {
            throw new RecordNotApplicable($category);
        }
    }
}

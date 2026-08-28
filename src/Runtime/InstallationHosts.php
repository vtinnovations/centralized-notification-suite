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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The hostnames this installation is configured to answer for, and the rules for comparing
 * one hostname with another.
 *
 * Two properties matter here and both are easy to get wrong:
 *
 * Comparison is exact. "example.com", "www.example.com", "shop.example.com" and
 * "admin.shop.example.com" are four different identities. Nothing in this class strips "www",
 * reduces a host to its registrable domain, follows an alias, or matches a suffix -- those
 * are the shortcuts that turn a single-site entitlement into a wildcard.
 *
 * The inventory comes from Contao's own root-page configuration, never from a request header.
 * Host, X-Forwarded-Host and friends are attacker-controlled; a check against them would let
 * anyone present whichever identity they liked.
 */
class InstallationHosts
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Every hostname configured on a root page, normalised, unique and sorted.
     *
     * Sorted so the value is stable for comparison and display; the sort has no security
     * meaning of its own. A root page with an empty "dns" contributes nothing: Contao treats
     * it as "answer for any host", which is precisely the ambiguity this check must not
     * inherit.
     *
     * @return list<string>
     */
    public function configured(): array
    {
        $this->framework->initialize();

        $pages = $this->framework->getAdapter(PageModel::class)->findBy(["type='root'"], []) ?? [];
        $hosts = [];

        foreach ($pages as $page) {
            if (null !== ($host = self::normalise((string) $page->dns))) {
                $hosts[$host] = true;
            }
        }

        $hosts = array_keys($hosts);
        sort($hosts, SORT_STRING);

        return $hosts;
    }

    /**
     * The hostname of the current request, but only when it is one this installation is
     * actually configured for.
     *
     * Returning null for an unrecognised host is the point: an unknown Host header must not
     * become the identity used for activation.
     */
    public function currentTrusted(): string|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        // getHost() applies Symfony's trusted-proxy/trusted-host configuration, which is the
        // framework-approved source; the raw Host header is never read directly here.
        $host = self::normalise($request->getHost());

        return null !== $host && \in_array($host, $this->configured(), true) ? $host : null;
    }

    /**
     * The host to present when talking to the issuing service.
     *
     * The current trusted host wins so that an administrator activating from the site they
     * are looking at binds that site; otherwise the first configured host is used so the
     * choice is deterministic and does not depend on who happens to be browsing. Null means
     * nothing is configured and activation must not be attempted.
     */
    public function verificationHost(): string|null
    {
        return $this->currentTrusted() ?? ($this->configured()[0] ?? null);
    }

    /**
     * The exact hostnames present in both the configured inventory and a signed set.
     *
     * This intersection is the activation predicate. It is set membership over normalised
     * strings -- never a suffix, wildcard, parent, child, sibling or alias relationship.
     *
     * @param list<string> $signed
     *
     * @return list<string>
     */
    public function intersect(array $signed): array
    {
        return array_values(array_intersect($this->configured(), $signed));
    }

    /**
     * Reduces a hostname to one canonical spelling, or rejects it.
     *
     * Only representation is changed -- case, one trailing dot, a port, and IDN spelling.
     * The labels themselves are never added to or removed, so normalisation can make two
     * spellings of the same host compare equal but can never widen what a host authorises.
     */
    public static function normalise(string $value): string|null
    {
        $value = trim($value);

        if ('' === $value) {
            return null;
        }

        // Accept a configured value written as a URL, which Contao's "dns" field permits
        if (str_contains($value, '://')) {
            $value = (string) parse_url($value, PHP_URL_HOST);
        }

        $value = strtolower(rtrim($value, '.'));

        // Strip a trailing port. A bare IPv6 literal is left alone: its colons are part of
        // the address, so blindly cutting at the last one would silently rewrite the host.
        if (preg_match('/^\[.*\]:\d+$/', $value) || (1 === substr_count($value, ':') && preg_match('/:\d+$/', $value))) {
            $value = preg_replace('/:\d+$/', '', $value) ?? $value;
        }

        if ('' === $value || str_contains($value, '*') || str_contains($value, '/')) {
            return null;
        }

        // IDN spellings are folded to Punycode so "münchen.de" and "xn--mnchen-3ya.de" are one
        // identity rather than two. A host already in ASCII is unaffected.
        if (preg_match('/[^\x20-\x7f]/', $value)) {
            $ascii = idn_to_ascii($value, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if (false === $ascii || '' === $ascii) {
                return null;
            }

            $value = strtolower($ascii);
        }

        // A hostname, or a bare IP which some installations legitimately use
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*$/', $value)
            && !filter_var(trim($value, '[]'), FILTER_VALIDATE_IP)
        ) {
            return null;
        }

        return $value;
    }
}

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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRecord;
use VTInnovations\CentralizedNotificationSuite\Runtime\RecordNotApplicable;
use VTInnovations\CentralizedNotificationSuite\Tests\Fixtures\SignedPackageFactory;

/**
 * The second half of trust: a record can be genuine and still not apply here.
 */
class ActivationRecordTest extends TestCase
{
    private SignedPackageFactory $issuer;

    private int $now;

    protected function setUp(): void
    {
        $this->issuer = new SignedPackageFactory();
        $this->now = 1800000000;
    }

    public function testAcceptsAPerpetualFreeRecord(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now), $this->now);

        $this->assertSame('free', $record->package);
        $this->assertTrue($record->lifetime);
        $this->assertNull($record->expiresAt);
        $this->assertSame('example.com', $record->host);
    }

    /**
     * This product is sold under one tier, so every other tier is inapplicable -- including
     * the more expensive ones. Accepting a "better" package would be a silent policy change.
     *
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('inapplicableRecords')]
    public function testRejectsRecordsThatDoNotApply(array $overrides, string $expected): void
    {
        $this->expectException(RecordNotApplicable::class);
        $this->expectExceptionMessage($expected);

        ActivationRecord::fromDocument($this->issuer->record($this->now, $overrides), $this->now);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function inapplicableRecords(): iterable
    {
        $now = 1800000000;

        yield 'a paid package' => [['license_package' => 'pro'], 'package_not_accepted'];
        yield 'a trial package' => [['license_package' => 'trial'], 'package_not_accepted'];
        yield 'a time-limited free package' => [['license_lifetime' => false, 'license_expires_at' => $now + 5000], 'lifetime_required'];
        yield 'perpetual but with an expiry' => [['license_expires_at' => $now + 5000], 'expiry_contradicts_lifetime'];
        yield 'temporary with no expiry' => [['license_lifetime' => false], 'expiry_contradicts_lifetime'];
        yield 'another product' => [['project' => 'Guardian'], 'wrong_product'];
        yield 'another slug' => [['project_slug' => 'guardian'], 'wrong_product'];
        yield 'a future schema' => [['schema_version' => 3], 'schema_unsupported'];
        yield 'a revoked record' => [['validation_status' => 'revoked'], 'record_not_valid'];
        yield 'no key' => [['license_key' => '   '], 'missing_key'];
        yield 'version below one' => [['license_version' => 0], 'bad_version'];
        yield 'not started yet' => [['license_starts_at' => $now + 5000], 'not_yet_valid'];
        // Reaches the date check rather than the lifetime check: the expiry is present and
        // internally consistent, it has simply passed.
        yield 'an expired record' => [['license_lifetime' => false, 'license_expires_at' => $now - 1], 'expired'];
    }

    /**
     * The signed host set is authorisation. Anything that would widen it is refused rather
     * than repaired, because the signature covers these exact strings.
     *
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('unusableHostSets')]
    public function testRejectsHostSetsThatAreNotCanonical(array $overrides, string $expected): void
    {
        $this->expectExceptionMessage($expected);

        ActivationRecord::fromDocument($this->issuer->record($this->now, $overrides), $this->now);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function unusableHostSets(): iterable
    {
        yield 'unsorted' => [['license_domains' => ['staging.example.com', 'example.com']], 'host_set_not_canonical'];
        yield 'duplicated' => [['license_domains' => ['example.com', 'example.com']], 'host_set_not_canonical'];
        yield 'wildcard' => [['license_domains' => ['*.example.com', 'example.com'], 'license_domain' => 'example.com'], 'malformed_host_set'];
        yield 'mixed case' => [['license_domains' => ['Example.com'], 'license_domain' => 'Example.com'], 'malformed_host_set'];
        yield 'trailing dot' => [['license_domains' => ['example.com.'], 'license_domain' => 'example.com.'], 'malformed_host_set'];
        yield 'empty' => [['license_domains' => []], 'missing_host_set'];
        yield 'operation host outside the set' => [['license_domain' => 'elsewhere.com'], 'host_not_in_set'];
        yield 'allowance of zero' => [['license_max_domains' => 0], 'bad_host_allowance'];
        yield 'negative allowance' => [['license_max_domains' => -1], 'bad_host_allowance'];
    }

    public function testAuthorisesOnlyExactMembersOfTheSignedSet(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now), $this->now);

        $this->assertTrue($record->authorises('example.com'));
        $this->assertTrue($record->authorises('staging.example.com'));

        // Every one of these is a different identity, and none of them is covered.
        $this->assertFalse($record->authorises('www.example.com'));
        $this->assertFalse($record->authorises('shop.example.com'));
        $this->assertFalse($record->authorises('example.com.evil.test'));
        $this->assertFalse($record->authorises('ample.com'));
    }

    /**
     * A large allowance is a number the issuer reports. It is not permission for hosts that
     * are not in the set.
     */
    public function testTheReportedAllowanceIsNotAWildcard(): void
    {
        $record = ActivationRecord::fromDocument(
            $this->issuer->record($this->now, ['license_max_domains' => 9999]),
            $this->now,
        );

        $this->assertFalse($record->authorises('anything.test'));
        $this->assertTrue($record->authorises('example.com'));
    }

    /**
     * The issuer lowers an allowance without unbinding hosts, so enforcing the count locally
     * would switch working installations off for a change made on the server.
     */
    public function testMoreBoundHostsThanTheAllowanceIsStillValid(): void
    {
        $record = ActivationRecord::fromDocument(
            $this->issuer->record($this->now, ['license_max_domains' => 1]),
            $this->now,
        );

        $this->assertCount(2, $record->hosts);
    }
}

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

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRecord;
use VTInnovations\CentralizedNotificationSuite\Runtime\ProductProfile;
use VTInnovations\CentralizedNotificationSuite\Runtime\RecordNotApplicable;
use VTInnovations\CentralizedNotificationSuite\Tests\Fixtures\SignedPackageFactory;

/**
 * Withdrawal of an entitlement, which is the half of licensing that is easy to get wrong.
 *
 * The mistake this guards against is treating "this record grants nothing" as "this record is
 * not genuine". They fail in opposite directions: refusing a malformed record leaves the site
 * as it was, which is safe, but refusing an authentic withdrawal leaves the site licensed,
 * which is the failure the whole mechanism exists to prevent.
 */
class RevocationTest extends TestCase
{
    private SignedPackageFactory $issuer;

    private int $now = 1800000000;

    protected function setUp(): void
    {
        $this->issuer = new SignedPackageFactory();
    }

    /**
     * The A -> B transfer, from A's side.
     *
     * A's withdrawal carries the new authorised set, and A has just been taken out of it. If
     * membership were required, A would refuse the one packet that ends A's entitlement.
     */
    public function testAWithdrawalMayNameAHostThatIsNoLongerAuthorised(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'validation_status' => ProductProfile::STATUS_REVOKED,
            'license_domain' => 'a.example.com',
            'license_domains' => ['b.example.com'],
            'license_version' => 9,
        ]), $this->now);

        $this->assertFalse($record->positive());
        $this->assertSame(ProductProfile::STATUS_REVOKED, $record->status);
        $this->assertSame('a.example.com', $record->host);
        $this->assertSame(9, $record->version);
    }

    /**
     * The same relaxation must not leak into a record that grants something, or a valid record
     * copied from another installation would be accepted here.
     */
    public function testAGrantingRecordStillRequiresItsHostToBeAuthorised(): void
    {
        $this->expectException(RecordNotApplicable::class);
        $this->expectExceptionMessage('host_not_in_set');

        ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'license_domain' => 'a.example.com',
            'license_domains' => ['b.example.com'],
        ]), $this->now);
    }

    /**
     * A withdrawal describes the past, so measuring it against the clock would reject exactly
     * the statement it exists to make.
     */
    public function testAWithdrawalIsReadableEvenThoughItsDatesHavePassed(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'validation_status' => ProductProfile::STATUS_EXPIRED,
            'license_lifetime' => false,
            'license_starts_at' => $this->now - 10000,
            'license_expires_at' => $this->now - 5000,
        ]), $this->now);

        $this->assertFalse($record->positive());
        $this->assertSame(ProductProfile::STATUS_EXPIRED, $record->status);
    }

    /**
     * A tier mismatch must never be a reason to stay licensed.
     *
     * This build accepts only the free package. A withdrawal naming any other one is still a
     * withdrawal, and refusing it because of the tier would leave the entitlement in place --
     * failing open, on the one path that must fail closed.
     */
    public function testAWithdrawalIsAcceptedWhateverTierItNames(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'validation_status' => ProductProfile::STATUS_REVOKED,
            'license_package' => 'pro',
            'license_lifetime' => false,
            'license_expires_at' => $this->now - 1,
        ]), $this->now);

        $this->assertFalse($record->positive());
    }

    public function testAGrantingRecordStillHasToMatchTheTier(): void
    {
        $this->expectException(RecordNotApplicable::class);
        $this->expectExceptionMessage('package_not_accepted');

        ActivationRecord::fromDocument($this->issuer->record($this->now, ['license_package' => 'pro']), $this->now);
    }

    /**
     * The host is still checked for shape. Relaxing set membership must not relax the rest,
     * or a withdrawal could name something that is not a hostname at all.
     */
    public function testAWithdrawalStillNeedsAWellFormedHost(): void
    {
        $this->expectException(RecordNotApplicable::class);
        $this->expectExceptionMessage('malformed_host');

        ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'validation_status' => ProductProfile::STATUS_REVOKED,
            'license_domain' => 'A.Example.COM./',
            'license_domains' => ['b.example.com'],
        ]), $this->now);
    }

    /**
     * The signed lease, which is what makes revocation eventually certain for an installation
     * that never receives the pushed withdrawal.
     */
    public function testTheSignedLeaseIsRead(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'license_refresh_required_at' => $this->now + 100,
            'license_grace_until' => $this->now + 900,
        ]), $this->now);

        $this->assertSame($this->now + 100, $record->refreshRequiredAt);
        $this->assertSame($this->now + 900, $record->graceUntil);
    }

    /**
     * A record from before the lease policy existed keeps working exactly as it did.
     */
    public function testARecordWithNoLeaseIsUnaffected(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now), $this->now);

        $this->assertNull($record->refreshRequiredAt);
        $this->assertNull($record->graceUntil);
    }

    /**
     * A deadline with no cutoff must bite at the deadline rather than never, which is what
     * would happen if the missing cutoff were read as "no limit".
     */
    public function testADeadlineWithNoCutoffBecomesItsOwnCutoff(): void
    {
        $record = ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'license_refresh_required_at' => $this->now + 100,
        ]), $this->now);

        $this->assertSame($this->now + 100, $record->graceUntil);
    }

    public function testACutoffBeforeItsDeadlineIsRefused(): void
    {
        $this->expectException(RecordNotApplicable::class);
        $this->expectExceptionMessage('bad_lease');

        ActivationRecord::fromDocument($this->issuer->record($this->now, [
            'license_refresh_required_at' => $this->now + 900,
            'license_grace_until' => $this->now + 100,
        ]), $this->now);
    }
}

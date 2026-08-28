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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Distribution;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Distribution\CanonicalForm;
use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Distribution\SealedPackage;
use VTInnovations\CentralizedNotificationSuite\Distribution\UnverifiedPackage;
use VTInnovations\CentralizedNotificationSuite\Tests\Fixtures\SignedPackageFactory;

class SealedPackageTest extends TestCase
{
    private SignedPackageFactory $issuer;

    private int $now;

    protected function setUp(): void
    {
        $this->issuer = new SignedPackageFactory();
        $this->now = 1800000000;
    }

    public function testOpensAGenuinePackageAndReturnsTheExactBytes(): void
    {
        [$response, $bytes] = $this->issuer->response($this->issuer->record($this->now));

        $package = SealedPackage::open($response, $this->issuer->keyring(), $this->now);

        // The stored bytes must be what was transmitted, byte for byte: the digest and the
        // signature were computed over these, and re-encoding would invalidate both.
        $this->assertSame($bytes, $package->bytes);
        $this->assertSame(md5($bytes), $package->envelope->license_md5);
    }

    /**
     * The order of checks is the security property, so this is the central test of the class:
     * an attacker who edits the record AND recomputes its digest must still be stopped,
     * because they cannot re-sign the envelope.
     */
    public function testRecomputingTheDigestForAnEditedRecordDoesNotHelp(): void
    {
        $edited = $this->issuer->record($this->now, ['license_package' => 'enterprise']);
        $bytes = SignedPackageFactory::bytes($edited);

        $response = (object) [
            'license_payload_b64' => base64_encode($bytes),
            'integrity' => (object) [
                'license_md5' => md5($bytes),
                'key_id' => 'test-key',
                'signature_algorithm' => IssuerKeyring::ALGORITHM,
                'signature' => base64_encode(random_bytes(SODIUM_CRYPTO_SIGN_BYTES)),
            ],
        ];

        $this->expectExceptionMessage('envelope_signature_invalid');

        SealedPackage::open($response, $this->issuer->keyring(), $this->now);
    }

    public function testASingleAlteredByteIsDetected(): void
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now));

        $bytes = base64_decode($response->license_payload_b64, true);
        $response->license_payload_b64 = base64_encode(str_replace('"free"', '"pro0"', $bytes));

        $this->expectExceptionMessage('digest_mismatch');

        SealedPackage::open($response, $this->issuer->keyring(), $this->now);
    }

    public function testAnEmptyRingFailsClosedRatherThanSkippingVerification(): void
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now));

        $this->expectExceptionMessage('signing_key_store_empty');

        SealedPackage::open($response, new IssuerKeyring([]), $this->now);
    }

    public function testARetiredKeyIsNoLongerAccepted(): void
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now));

        $this->expectException(UnverifiedPackage::class);

        SealedPackage::open($response, $this->issuer->keyring('test-key', 0, $this->now - 1), $this->now);
    }

    public function testAKeyIsNotUsedBeforeItsActivationTime(): void
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now));

        $this->expectException(UnverifiedPackage::class);

        SealedPackage::open($response, $this->issuer->keyring('test-key', $this->now + 1000), $this->now);
    }

    /**
     * @param array<string, mixed> $envelope
     */
    #[DataProvider('rejectedEnvelopes')]
    public function testRejectsMalformedEnvelopes(array $envelope, string $expected): void
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now), 'test-key', $envelope);

        $this->expectExceptionMessage($expected);

        SealedPackage::open($response, $this->issuer->keyring(), $this->now);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function rejectedEnvelopes(): iterable
    {
        yield 'unknown key id' => [['key_id' => 'not-pinned'], 'unknown_signing_key'];
        yield 'algorithm downgrade' => [['signature_algorithm' => 'none'], 'unsupported_signature_algorithm'];
        yield 'unexpected algorithm' => [['signature_algorithm' => 'rsa-sha256'], 'unsupported_signature_algorithm'];
    }

    public function testRejectsAMissingOrUnusablePayload(): void
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now));
        $response->license_payload_b64 = 'not valid base64 !!!';

        $this->expectExceptionMessage('malformed_payload');

        SealedPackage::open($response, $this->issuer->keyring(), $this->now);
    }

    public function testRejectsAResponseWithNoEnvelope(): void
    {
        $this->expectExceptionMessage('missing_integrity_envelope');

        SealedPackage::open((object) ['license_payload_b64' => 'x'], $this->issuer->keyring(), $this->now);
    }

    /**
     * A record signed by a different issuer verifies against neither key, even when its
     * envelope is otherwise well formed.
     */
    public function testARecordSignedByAnotherPartyIsRejected(): void
    {
        $other = new SignedPackageFactory();
        $foreign = $other->record($this->now);

        $bytes = SignedPackageFactory::bytes($foreign);
        $envelope = $this->issuer->sign([
            'license_md5' => md5($bytes),
            'key_id' => 'test-key',
            'signature_algorithm' => IssuerKeyring::ALGORITHM,
        ]);

        $this->expectExceptionMessage('document_signature_invalid');

        SealedPackage::open(
            (object) ['license_payload_b64' => base64_encode($bytes), 'integrity' => $envelope],
            $this->issuer->keyring(),
            $this->now,
        );
    }

    /**
     * Reordering members changes the bytes but not the canonical form, so a genuine record
     * that has merely been re-serialised by a proxy must still verify.
     */
    public function testCanonicalFormIsIndependentOfMemberOrder(): void
    {
        $record = $this->issuer->record($this->now);

        $shuffled = new \stdClass();

        foreach (array_reverse(array_keys(get_object_vars($record))) as $name) {
            $shuffled->$name = $record->$name;
        }

        $this->assertSame(CanonicalForm::bytes($record), CanonicalForm::bytes($shuffled));
    }
}

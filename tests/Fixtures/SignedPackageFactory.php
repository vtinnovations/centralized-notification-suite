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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Fixtures;

use VTInnovations\CentralizedNotificationSuite\Distribution\CanonicalForm;
use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Runtime\ProductProfile;

/**
 * Builds signed packages for the tests.
 *
 * The key pair here is generated per test run and is emphatically NOT the issuing service's
 * key. It exists so the verification path can be exercised against material this repository
 * is allowed to produce: signing a positive vector with the real key would require the real
 * private key, which lives only on V-T.ONE infrastructure and must never be here.
 *
 * What that buys is a genuine end-to-end test of canonicalisation, envelope handling, digest
 * comparison and signature verification. What it cannot prove is interoperability with the
 * real issuer -- that needs an authentic signed sample, which is recorded as an outstanding
 * external dependency rather than faked here.
 */
final class SignedPackageFactory
{
    public readonly string $publicKey;

    private readonly string $secretKey;

    public function __construct()
    {
        $pair = sodium_crypto_sign_keypair();
        $this->publicKey = sodium_crypto_sign_publickey($pair);
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
    }

    /**
     * A ring pinning only this factory's key, under the given id.
     */
    public function keyring(string $keyId = 'test-key', int $from = 0, int|null $until = null): IssuerKeyring
    {
        return new IssuerKeyring([
            $keyId => [
                [base64_encode($this->publicKey)],
                substr(hash('sha256', $this->publicKey), 0, 16),
                $from,
                $until,
            ],
        ]);
    }

    /**
     * A record document with valid defaults, overridable field by field.
     *
     * @param array<string, mixed> $overrides
     */
    public function record(int $now, array $overrides = []): \stdClass
    {
        return $this->sign(array_merge([
            'schema_version' => ProductProfile::SCHEMA,
            'project' => ProductProfile::NAME,
            'project_slug' => ProductProfile::SLUG,
            'license_key' => 'CNS-TEST-KEY',
            'license_domain' => 'example.com',
            'license_domains' => ['example.com', 'staging.example.com'],
            'license_max_domains' => 3,
            'license_package' => 'free',
            'license_features' => [],
            'license_version' => 7,
            'license_issued_at' => $now - 1000,
            'license_starts_at' => $now - 1000,
            'license_expires_at' => null,
            'license_lifetime' => true,
            'license_verified_at' => $now,
            'free_available' => true,
            'validation_status' => 'valid',
        ], $overrides));
    }

    /**
     * A complete response envelope around a record.
     *
     * @param array<string, mixed> $envelopeOverrides
     *
     * @return array{0: \stdClass, 1: string} the response and the exact record bytes
     */
    public function response(\stdClass $record, string $keyId = 'test-key', array $envelopeOverrides = []): array
    {
        $bytes = self::bytes($record);

        $envelope = $this->sign(array_merge([
            'project' => ProductProfile::NAME,
            'project_slug' => ProductProfile::SLUG,
            'license_version' => $record->license_version ?? 1,
            'license_md5' => md5($bytes),
            'generated_at' => time(),
            'key_id' => $keyId,
            'signature_algorithm' => IssuerKeyring::ALGORITHM,
        ], $envelopeOverrides));

        return [
            (object) [
                'status' => 'valid',
                'license_payload_b64' => base64_encode($bytes),
                'integrity' => $envelope,
            ],
            $bytes,
        ];
    }

    /**
     * The exact bytes a record is transmitted and stored as.
     */
    public static function bytes(\stdClass $document): string
    {
        return (string) json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function sign(array $fields): \stdClass
    {
        $document = CanonicalForm::decode((string) json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $document->signature = base64_encode(sodium_crypto_sign_detached(CanonicalForm::bytes($document), $this->secretKey));

        return $document;
    }

    /**
     * Signs an arbitrary message, for the inbound request format.
     */
    public function signMessage(string $message): string
    {
        return base64_encode(sodium_crypto_sign_detached($message, $this->secretKey));
    }
}

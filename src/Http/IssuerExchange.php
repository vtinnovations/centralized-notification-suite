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

use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Distribution\SealedPackage;
use VTInnovations\CentralizedNotificationSuite\Distribution\UnverifiedPackage;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRecord;
use VTInnovations\CentralizedNotificationSuite\Runtime\ProductProfile;
use VTInnovations\CentralizedNotificationSuite\Runtime\RecordNotApplicable;

/**
 * Asks the issuing service for a record and checks everything that comes back.
 *
 * The order below is the security property. Each step assumes only what the steps before it
 * established, and the result is not handed to the caller -- let alone stored -- until all of
 * them have passed:
 *
 *   1. the reply is a successful JSON document of the expected shape;
 *   2. it answers the request that was actually sent (request id correlation);
 *   3. its seal and signatures hold, and its digest matches the exact bytes;
 *   4. the record inside applies to this product, tier and moment;
 *   5. the host it was issued for is the host that was asked about;
 *   6. that host is one this installation is configured for.
 *
 * Nothing here writes to disk. Persistence is a separate concern precisely so that a
 * half-verified package has no route to becoming stored state.
 */
class IssuerExchange
{
    public function __construct(
        private readonly SecurePost $post,
        private readonly IssuerKeyring $keys,
    ) {
    }

    /**
     * First activation with a freshly entered key.
     *
     * @throws TransportFailed|UnverifiedPackage|RecordNotApplicable
     */
    public function activate(string $key, string $host, int $now): SealedPackage
    {
        return $this->exchange('activate', $key, $host, $now, null);
    }

    /**
     * Re-checks an existing record.
     *
     * The stored version travels with the request so the service can tell whether anything
     * material has changed and can refuse to hand back something older.
     *
     * @throws TransportFailed|UnverifiedPackage|RecordNotApplicable
     */
    public function refresh(string $key, string $host, int $now, int $currentVersion): SealedPackage
    {
        return $this->exchange('refresh', $key, $host, $now, $currentVersion);
    }

    /**
     * @throws TransportFailed|UnverifiedPackage|RecordNotApplicable
     */
    private function exchange(string $action, string $key, string $host, int $now, int|null $currentVersion): SealedPackage
    {
        $requestId = self::identifier();

        $payload = [
            'action' => $action,
            'project' => ProductProfile::NAME,
            'project_slug' => ProductProfile::SLUG,
            'product_id' => ProductProfile::CATALOGUE_ID,
            'license_key' => $key,
            'domain' => $host,
            'request_id' => $requestId,
            'timestamp' => $now,
            // Single use and unpredictable, so a captured request cannot be replayed
            'nonce' => self::identifier(),
        ];

        if (null !== $currentVersion) {
            $payload['current_license_version'] = $currentVersion;
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (false === $body) {
            throw new TransportFailed('request_not_encodable');
        }

        $document = $this->post->send(ServiceEndpoints::verification(), $body)->document();

        // Correlation before anything else is trusted: an answer to a different question,
        // replayed by whoever sits in the middle, must not be accepted as this one's.
        $answeredId = $document->request_id ?? null;

        if (!\is_string($answeredId) || !hash_equals($requestId, $answeredId)) {
            throw new TransportFailed('request_correlation_failed');
        }

        if ('valid' !== ($document->status ?? null)) {
            throw new TransportFailed('declined');
        }

        $package = SealedPackage::open($document, $this->keys, $now);
        $record = ActivationRecord::fromDocument($package->document, $now);

        // The service must have answered about the host that was asked about. Without this a
        // record legitimately issued for one site could be presented on another.
        if (!hash_equals($host, $record->host)) {
            throw new RecordNotApplicable('host_mismatch');
        }

        return $package;
    }

    /**
     * Unpredictable, single-use, and short enough to travel in a header.
     */
    private static function identifier(): string
    {
        return bin2hex(random_bytes(16));
    }
}

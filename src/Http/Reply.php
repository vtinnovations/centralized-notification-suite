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

use VTInnovations\CentralizedNotificationSuite\Distribution\CanonicalForm;
use VTInnovations\CentralizedNotificationSuite\Distribution\MalformedDocument;

/**
 * What came back from the issuing service.
 *
 * Holds the raw body but exposes it only through document(), which refuses to parse anything
 * that is not a successful JSON response. The body is never returned for logging or display:
 * it can contain a full licence key and a signed payload, none of which may reach a log file
 * or a browser.
 */
final class Reply
{
    public function __construct(
        public readonly int $status,
        private readonly string $contentType,
        private readonly string $body,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * The parsed response, or a failure category.
     *
     * The media type is checked before parsing: an HTML error page from a proxy must be
     * refused as the wrong shape rather than half-parsed into something surprising.
     *
     * @throws TransportFailed
     */
    public function document(): \stdClass
    {
        if (!$this->isSuccessful()) {
            // Status only. A remote error body may quote internal detail and is not kept.
            throw new TransportFailed('http_'.$this->status);
        }

        if (!str_contains(strtolower($this->contentType), 'application/json')) {
            throw new TransportFailed('unexpected_media_type');
        }

        try {
            return CanonicalForm::decode($this->body);
        } catch (MalformedDocument $e) {
            throw new TransportFailed('malformed_response', 0, $e);
        }
    }
}

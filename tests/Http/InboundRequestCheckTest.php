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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Http;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Http\InboundRejected;
use VTInnovations\CentralizedNotificationSuite\Http\InboundRequestCheck;
use VTInnovations\CentralizedNotificationSuite\Http\ServiceEndpoints;
use VTInnovations\CentralizedNotificationSuite\Tests\Fixtures\SignedPackageFactory;

/**
 * This endpoint is reachable without a session, so the signature is the only thing standing
 * between it and the internet.
 */
class InboundRequestCheckTest extends TestCase
{
    private SignedPackageFactory $issuer;

    private InboundRequestCheck $check;

    private int $now;

    private string $body;

    protected function setUp(): void
    {
        $this->issuer = new SignedPackageFactory();
        $this->check = new InboundRequestCheck($this->issuer->keyring());
        $this->now = 1800000000;
        $this->body = '{"action":"license_update","request_id":"r-1","timestamp":'.$this->now.',"nonce":"n-1"}';
    }

    public function testAcceptsACorrectlySignedRequest(): void
    {
        $this->expectNotToPerformAssertions();

        $this->check->verify($this->request(), $this->body, $this->now);
    }

    public function testRejectsAnUnsignedRequest(): void
    {
        $request = $this->request(sign: false);

        $this->expectExceptionMessage('malformed_signature');

        $this->check->verify($request, $this->body, $this->now);
    }

    /**
     * The signature covers a hash of the raw body, so any edit after signing is detected even
     * though every header still looks right.
     */
    public function testRejectsABodyEditedAfterSigning(): void
    {
        $signed = $this->request();

        $tampered = $this->request(body: str_replace('"n-1"', '"n-1","extra":true', $this->body), sign: false);
        $tampered->headers->set('X-VT-Signature', (string) $signed->headers->get('X-VT-Signature'));

        $this->expectExceptionMessage('signature_invalid');

        $this->check->verify($tampered, (string) $tampered->getContent(), $this->now);
    }

    /**
     * The path is one of the signed lines, so a signature captured from another endpoint on
     * the same host cannot be replayed here.
     */
    public function testASignatureIsBoundToItsPath(): void
    {
        $elsewhere = $this->request(path: '/rest/api/v1/other-license-updater');

        $here = $this->request(sign: false);
        $here->headers->set('X-VT-Signature', (string) $elsewhere->headers->get('X-VT-Signature'));

        $this->expectExceptionMessage('signature_invalid');

        $this->check->verify($here, $this->body, $this->now);
    }

    public function testRejectsAStaleRequest(): void
    {
        $this->expectExceptionMessage('stale_timestamp');

        $this->check->verify($this->request(timestamp: $this->now - 4000), $this->body, $this->now);
    }

    /**
     * A timestamp from the future is as suspicious as an old one and is refused the same way.
     */
    public function testRejectsARequestFromTheFuture(): void
    {
        $this->expectExceptionMessage('stale_timestamp');

        $this->check->verify($this->request(timestamp: $this->now + 4000), $this->body, $this->now);
    }

    public function testRejectsAnUnpinnedKeyId(): void
    {
        $this->expectExceptionMessage('unknown_signing_key');

        $this->check->verify($this->request(keyId: 'not-pinned'), $this->body, $this->now);
    }

    public function testFailsClosedWhenNoKeyIsPinned(): void
    {
        $check = new InboundRequestCheck(new IssuerKeyring([]));

        $this->expectExceptionMessage('signing_key_store_empty');

        $check->verify($this->request(), $this->body, $this->now);
    }

    /**
     * Only the header copies are signed, so the body copies have to be compared against them
     * or an attacker could edit whichever one the application actually reads.
     */
    public function testRejectsDisagreementBetweenHeadersAndBody(): void
    {
        $body = '{"request_id":"different","timestamp":'.$this->now.',"nonce":"n-1"}';
        $request = $this->request(body: $body);

        $this->expectException(InboundRejected::class);
        $this->expectExceptionMessage('header_body_mismatch');

        $this->check->requireAgreement($request, json_decode($body, false));
    }

    public function testAcceptsAgreeingHeadersAndBody(): void
    {
        $this->expectNotToPerformAssertions();

        $this->check->requireAgreement($this->request(), json_decode($this->body, false));
    }

    private function request(
        string|null $path = null,
        string|null $body = null,
        int|null $timestamp = null,
        string $keyId = 'test-key',
        bool $sign = true,
    ): Request {
        $path ??= ServiceEndpoints::updatePath();
        $body ??= $this->body;
        $timestamp ??= $this->now;

        $request = Request::create($path, 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $request->headers->set('X-VT-Request-ID', 'r-1');
        $request->headers->set('X-VT-Timestamp', (string) $timestamp);
        $request->headers->set('X-VT-Nonce', 'n-1');
        $request->headers->set('X-VT-Key-ID', $keyId);

        if ($sign) {
            $request->headers->set('X-VT-Signature', $this->issuer->signMessage(implode("\n", [
                'POST',
                $request->getPathInfo(),
                'r-1',
                (string) $timestamp,
                'n-1',
                hash('sha256', $body),
            ])));
        }

        return $request;
    }
}

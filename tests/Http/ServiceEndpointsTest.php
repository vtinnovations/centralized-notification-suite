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
use VTInnovations\CentralizedNotificationSuite\Controller\StateUpdateEndpoint;
use VTInnovations\CentralizedNotificationSuite\Http\SecurePost;
use VTInnovations\CentralizedNotificationSuite\Http\ServiceEndpoints;
use VTInnovations\CentralizedNotificationSuite\Http\TransportFailed;
use VTInnovations\CentralizedNotificationSuite\Runtime\ProductProfile;

class ServiceEndpointsTest extends TestCase
{
    /**
     * The destinations are part of the trust model. If one of them silently changed, every
     * other control in this product would be protecting the wrong conversation.
     */
    public function testDestinationsAreExactlyTheAgreedOnes(): void
    {
        $this->assertSame('https://www.v-t.one/api/v1/verify', ServiceEndpoints::verification());
        $this->assertSame('https://www.v-t.one/rest/api/v1/log-envoke', ServiceEndpoints::signal());
    }

    public function testTheInboundPathFollowsTheProductSlug(): void
    {
        $this->assertSame(
            '/rest/api/v1/'.ProductProfile::SLUG.'-license-updater',
            ServiceEndpoints::updatePath(),
        );
    }

    /**
     * The route is declared in an attribute, which cannot call a method, so the literal there
     * and the computed path can drift apart. They must not: the issuing service posts to the
     * computed one and would get a 404.
     */
    public function testTheDeclaredRouteMatchesTheComputedPath(): void
    {
        $source = (string) file_get_contents((new \ReflectionClass(StateUpdateEndpoint::class))->getFileName());

        $this->assertSame(1, preg_match("#path: '([^']+)'#", $source, $matches));
        $this->assertSame(ServiceEndpoints::updatePath(), $matches[1]);
    }

    public function testOnlyThePinnedDestinationsAreTrusted(): void
    {
        $this->assertTrue(ServiceEndpoints::isTrusted(ServiceEndpoints::verification()));
        $this->assertTrue(ServiceEndpoints::isTrusted(ServiceEndpoints::signal()));

        $this->assertFalse(ServiceEndpoints::isTrusted('https://evil.test/api/v1/verify'));
        $this->assertFalse(ServiceEndpoints::isTrusted('http://www.v-t.one/api/v1/verify'));
        $this->assertFalse(ServiceEndpoints::isTrusted('https://www.v-t.one.evil.test/api/v1/verify'));
        $this->assertFalse(ServiceEndpoints::isTrusted('https://user@www.v-t.one/api/v1/verify'));
    }

    /**
     * The transport refuses anything that is not a pinned destination, so a mistake elsewhere
     * cannot put a licence key on the wire to an arbitrary host. No request is made.
     */
    public function testTheTransportRefusesAnUntrustedDestination(): void
    {
        $this->expectException(TransportFailed::class);
        $this->expectExceptionMessage('untrusted_destination');

        (new SecurePost())->send('https://evil.test/collect', '{}');
    }
}

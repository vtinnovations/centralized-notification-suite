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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use VTInnovations\CentralizedNotificationSuite\Http\SecurePost;
use VTInnovations\CentralizedNotificationSuite\Http\ServiceEndpoints;
use VTInnovations\CentralizedNotificationSuite\Http\TransportFailed;

/**
 * The two notices this product sends about its own use.
 *
 * They are separate events with separate payloads, and they must not be merged into one
 * general-purpose telemetry call:
 *
 *   - the invocation notice says only which product ran and on which host;
 *   - the session notice additionally carries the licence key, once, when an administrator
 *     first opens the product's section in a backend session.
 *
 * The key is the single exception to the rule that keys stay on this server, and it is a
 * narrow one. It comes only from a record whose signature verified, it goes only to the fixed
 * endpoint over TLS, and it never reaches a log line, a template, a session marker or the
 * browser. If no verified record exists there is no key to send and nothing is sent.
 *
 * Neither notice can affect anything. They are deferred, given a short timeout, and their
 * failures are swallowed: a site must not slow down, break, or lose entitlement because a
 * remote logging endpoint is having a bad day.
 */
class UsageSignals
{
    /**
     * Short on purpose. Nothing waits on the answer, and a request is deferred until after
     * the response has been sent, but a hung socket still occupies a worker.
     */
    private const TIMEOUT = 4;

    /**
     * Session key under which the claim is recorded.
     *
     * Holds a version marker, never the key or the host. Anything written here is readable by
     * whoever can read the session store.
     */
    private const CLAIM = 'cns_section_seen';

    private bool $invocationSent = false;

    /**
     * Payloads waiting to go out after the response has been sent.
     *
     * @var list<array<string, string>>
     */
    private array $queued = [];

    public function __construct(
        private readonly SecurePost $post,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * At most once per request, when the product has actually done something.
     *
     * Tied to real work rather than to every page view: an installation that never sends a
     * notification is not invoking this product, and reporting otherwise would be both noisy
     * and untrue.
     */
    public function invoked(string $host): void
    {
        if ($this->invocationSent) {
            return;
        }

        $this->invocationSent = true;

        $this->send(['project' => ProductProfile::NAME, 'domain' => $host]);
    }

    /**
     * Once per authenticated backend session, when the product's own section is first opened.
     *
     * The claim is written before the request is attempted, so a timeout cannot turn into a
     * retry loop that sends the key on every reload. Parallel tabs race for the same session
     * value and only one of them wins.
     */
    public function sectionOpened(Activation $activation): void
    {
        $key = $activation->key();

        if (null === $key || null === $activation->host) {
            // Nothing authentic to report. Never invent a key for an unlicensed install.
            return;
        }

        $session = $this->requestStack->getCurrentRequest()?->getSession();

        if (null === $session || !$session->isStarted()) {
            return;
        }

        // Marked by version so a refresh that changes the record is reported once more, while
        // ordinary navigation within the session is not.
        $marker = 'v'.$activation->version();

        if ($session->get(self::CLAIM) === $marker) {
            return;
        }

        $session->set(self::CLAIM, $marker);

        $this->send(['domain' => $activation->host, 'key' => $key]);
    }

    /**
     * Clears the claim so a later session reports again.
     */
    public function forgetClaim(): void
    {
        $this->requestStack->getCurrentRequest()?->getSession()?->remove(self::CLAIM);
    }

    /**
     * Delivers whatever was queued during this request.
     *
     * Called after the response has been sent, so a visitor submitting a form never waits on
     * a logging endpoint and a slow one cannot make a page appear broken.
     */
    public function flush(): void
    {
        $pending = $this->queued;
        $this->queued = [];

        foreach ($pending as $payload) {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (false === $body) {
                continue;
            }

            try {
                $this->post->send(ServiceEndpoints::signal(), $body, [], self::TIMEOUT);
            } catch (TransportFailed $e) {
                // Category only. The payload must never be logged: one of these two shapes
                // carries the licence key.
                $this->logger->debug('A usage signal could not be delivered ('.$e->getMessage().').');
            }
        }
    }

    /**
     * @param array<string, string> $payload
     */
    private function send(array $payload): void
    {
        // Queued rather than sent: the claim has already been recorded, so a failure here
        // must not be retried, and nothing in the request may block on the result.
        $this->queued[] = $payload;
    }
}

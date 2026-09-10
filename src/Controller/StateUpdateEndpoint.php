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

namespace VTInnovations\CentralizedNotificationSuite\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use VTInnovations\CentralizedNotificationSuite\Distribution\CanonicalForm;
use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Distribution\MalformedDocument;
use VTInnovations\CentralizedNotificationSuite\Distribution\SealedPackage;
use VTInnovations\CentralizedNotificationSuite\Distribution\UnverifiedPackage;
use VTInnovations\CentralizedNotificationSuite\Http\InboundRejected;
use VTInnovations\CentralizedNotificationSuite\Http\InboundRequestCheck;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRecord;
use VTInnovations\CentralizedNotificationSuite\Runtime\InstallationHosts;
use VTInnovations\CentralizedNotificationSuite\Runtime\ProductProfile;
use VTInnovations\CentralizedNotificationSuite\Runtime\RecordNotApplicable;
use VTInnovations\CentralizedNotificationSuite\Store\ActivationStore;
use VTInnovations\CentralizedNotificationSuite\Store\ExchangeJournal;
use VTInnovations\CentralizedNotificationSuite\Store\StateNotWritable;

/**
 * Accepts a record pushed by the issuing service.
 *
 * The path is public because it has to be: the sender is a server, not a logged-in browser,
 * so there is no session and no CSRF token to check. Everything that establishes trust is
 * carried in the request signature, which is why this class stays thin -- it limits the shape
 * of what it will look at, then hands the work to components that are tested on their own.
 *
 * It never writes anything but the one state pair, and never takes a path, a filename or a
 * destination from the request. There is deliberately no route by which this endpoint can
 * touch anything else on disk.
 */
class StateUpdateEndpoint
{
    /**
     * Generous for a signed package, small enough that an unauthenticated caller cannot make
     * the server buffer anything meaningful.
     */
    private const MAX_BODY = 262144;

    public function __construct(
        private readonly InboundRequestCheck $check,
        private readonly IssuerKeyring $keys,
        private readonly ActivationStore $store,
        private readonly ActivationGate $gate,
        private readonly InstallationHosts $hosts,
        private readonly ExchangeJournal $journal,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/rest/api/v1/centralized-notification-suite-license-updater',
        name: 'centralized_notification_suite_state_update',
        defaults: ['_scope' => 'frontend', '_token_check' => false],
        methods: ['POST', 'GET', 'PUT', 'PATCH', 'DELETE', 'HEAD'],
    )]
    public function __invoke(Request $request): Response
    {
        // Answering 405 rather than 404 tells the sender the endpoint exists and it used the
        // wrong verb, which is the difference between "not deployed" and "wrong call".
        if ('POST' !== $request->getMethod()) {
            return new Response('', Response::HTTP_METHOD_NOT_ALLOWED, ['Allow' => 'POST']);
        }

        if (!str_contains(strtolower((string) $request->headers->get('Content-Type', '')), 'application/json')) {
            return new Response('', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $raw = $request->getContent();

        if (\strlen($raw) > self::MAX_BODY) {
            return new Response('', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $now = time();

        try {
            $this->check->verify($request, $raw, $now);
            $body = CanonicalForm::decode($raw);
            $this->check->requireAgreement($request, $body);
        } catch (InboundRejected|MalformedDocument $e) {
            // One shape of answer for every reason, so the endpoint cannot be used to probe
            // which part of a forged request was wrong.
            $this->logger->warning('An inbound state update was rejected: '.$e->getMessage());

            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        return $this->apply($body, $raw, $now);
    }

    private function apply(\stdClass $body, string $raw, int $now): Response
    {
        $requestId = (string) ($body->request_id ?? '');
        $outcome = $this->journal->claim($requestId, $raw, (string) ($body->nonce ?? ''), 0, $now);

        if ($outcome->isRepeat) {
            // The sender did not get the first answer. Repeating it costs nothing and
            // re-applying would risk moving state twice.
            return $this->json(['status' => 'already_processed', 'request_id' => $requestId, 'license_version' => $outcome->appliedVersion]);
        }

        if (!$outcome->isNew) {
            $this->logger->warning('An inbound state update reused a request id with different content.');

            return new Response('', Response::HTTP_FORBIDDEN);
        }

        try {
            $package = SealedPackage::open($body, $this->keys, $now);
            $record = ActivationRecord::fromDocument($package->document, $now);

            if ('license_update' !== ($body->action ?? null)
                || ProductProfile::SLUG !== ($body->project_slug ?? null)
                || ProductProfile::CATALOGUE_ID !== ($body->product_id ?? null)
            ) {
                throw new RecordNotApplicable('wrong_product');
            }

            // The host named in the envelope of the request must be the host the record was
            // issued for, and that host must be one this site answers for.
            if (!\is_string($body->domain ?? null) || !hash_equals($record->host, $body->domain)) {
                throw new RecordNotApplicable('host_mismatch');
            }

            $this->requireThisInstallation($record);

            // An older record is genuine and would pass every signature check, so the only
            // thing standing between a revocation and its undo is this comparison. It is made
            // against the durable watermark, not against whatever is currently granted: a
            // revoked installation grants nothing, and reading its version as zero would let
            // every superseded record back in.
            $floor = $this->store->watermark()['version'] ?? 0;

            if ($record->version < $floor) {
                throw new RecordNotApplicable('would_roll_back');
            }

            $this->store->write($package->bytes, $package->envelope);
            $this->store->raiseWatermark($record->version, $record->status);
        } catch (UnverifiedPackage|RecordNotApplicable|StateNotWritable $e) {
            $this->journal->release($requestId);
            $this->logger->warning('An inbound state update was not applied: '.$e->getMessage());

            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $this->gate->forget();
        $this->journal->recordApplied($requestId, $record->version);

        // Version and status only. The body that carried them holds a full key and a signed
        // payload, and none of that belongs in an ordinary log line.
        $this->logger->info(\sprintf('An inbound state update applied version %d as "%s".', $record->version, $record->status));

        return $this->json(['status' => 'updated', 'request_id' => $requestId, 'license_version' => $record->version]);
    }

    /**
     * Checks that the record is addressed to this installation.
     *
     * The rule differs by what the record does, and getting that wrong in either direction is
     * a security bug:
     *
     * A record that grants must name a host this site is configured for *within its authorised
     * set*, so a valid record copied from another site is refused.
     *
     * A record that withdraws must name a host this site is configured for, and nothing more.
     * Its authorised set is the new one, which is precisely where the host being withdrawn has
     * just been removed from -- when a licence moves from A to B, A's withdrawal carries
     * license_domains=[B]. Requiring set membership here would make A refuse the one packet
     * that ends A's entitlement, and a failed withdrawal leaves the site licensed.
     *
     * @throws RecordNotApplicable
     */
    private function requireThisInstallation(ActivationRecord $record): void
    {
        if ($record->positive()) {
            if ([] === $this->hosts->intersect($record->hosts)) {
                throw new RecordNotApplicable('no_configured_host');
            }

            return;
        }

        if (!\in_array($record->host, $this->hosts->configured(), true)) {
            throw new RecordNotApplicable('not_this_installation');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload): Response
    {
        return new Response(
            (string) json_encode($payload, JSON_UNESCAPED_SLASHES),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
        );
    }
}

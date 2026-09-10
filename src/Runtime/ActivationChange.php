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
use VTInnovations\CentralizedNotificationSuite\Distribution\SealedPackage;
use VTInnovations\CentralizedNotificationSuite\Distribution\UnverifiedPackage;
use VTInnovations\CentralizedNotificationSuite\Http\IssuerExchange;
use VTInnovations\CentralizedNotificationSuite\Http\TransportFailed;
use VTInnovations\CentralizedNotificationSuite\Store\ActivationStore;
use VTInnovations\CentralizedNotificationSuite\Store\StateNotWritable;

/**
 * The three things an administrator can do, and the only paths that write stored state.
 *
 * One rule governs all of them: a failure never costs a working installation its record. A
 * timeout, a 500, an unreadable answer -- none of those say anything about the record already
 * on disk, so none of them may remove it. Only an authenticated newer record replaces one,
 * and only an explicit removal clears one.
 *
 * Rollback is refused for the same reason: an older signed record is genuine, so signature
 * checks alone would happily accept it. Replaying yesterday's package to undo a revocation is
 * exactly the attack, which is why the version is compared before the write.
 */
class ActivationChange
{
    public function __construct(
        private readonly IssuerExchange $exchange,
        private readonly ActivationStore $store,
        private readonly ActivationGate $gate,
        private readonly InstallationHosts $hosts,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Activates with a newly entered key.
     *
     * @throws ActivationRefused
     */
    public function activate(string $key): Activation
    {
        $key = trim($key);

        if ('' === $key) {
            throw new ActivationRefused('key_missing');
        }

        return $this->apply(fn (string $host, int $now): SealedPackage => $this->exchange->activate($key, $host, $now), 'activate');
    }

    /**
     * Re-checks the stored record, optionally swapping in a replacement key.
     *
     * With no replacement the stored key is reused, which means the browser never has to send
     * a key back that the server already holds.
     *
     * @throws ActivationRefused
     */
    public function refresh(string $replacementKey = ''): Activation
    {
        $replacementKey = trim($replacementKey);
        $current = $this->gate->current();
        $key = '' !== $replacementKey ? $replacementKey : $current->key();

        if (null === $key || '' === $key) {
            // A withdrawn installation grants nothing, so current()->key() is null -- but the
            // record is still on disk and is exactly what the refresh job needs to send in
            // order to be told about a reinstatement.
            $key = $this->gate->storedRecord()?->key;
        }

        if (null === $key || '' === $key) {
            throw new ActivationRefused('nothing_to_refresh');
        }

        // The highest version this installation has accepted, so the issuer is told where the
        // client actually is rather than zero -- which is what a withdrawn state reports.
        $version = max($current->version(), $this->store->watermark()['version'] ?? 0);

        return $this->apply(
            fn (string $host, int $now): SealedPackage => $this->exchange->refresh($key, $host, $now, $version),
            'refresh',
        );
    }

    /**
     * Removes the record, returning the product to its unlicensed behaviour immediately.
     *
     * Local only, and never blocked by the network being down -- an administrator must always
     * be able to revoke state on their own installation.
     */
    public function remove(): Activation
    {
        $this->store->clear();
        $this->gate->forget();

        $this->logger->info('Product activation removed by an administrator.');

        return $this->gate->current();
    }

    /**
     * @param callable(string, int): SealedPackage $call
     *
     * @throws ActivationRefused
     */
    private function apply(callable $call, string $operation): Activation
    {
        $host = $this->hosts->verificationHost();

        if (null === $host) {
            // Nothing to bind to. Better to say so than to send a guessed hostname and have
            // the record bound to something the site does not actually answer for.
            throw new ActivationRefused('no_configured_host');
        }

        $now = time();

        try {
            $package = $call($host, $now);
        } catch (TransportFailed|UnverifiedPackage|RecordNotApplicable $e) {
            // Category only: the response body may carry the key and the signed payload.
            $this->logger->warning(\sprintf('Product %s did not complete (%s).', $operation, $e->getMessage()));

            throw new ActivationRefused($e->getMessage(), $e);
        }

        $incoming = ActivationRecord::fromDocument($package->document, $now);

        // Against the durable watermark, not against what is currently granted. A withdrawn
        // installation grants nothing and reports version zero, and measuring against that
        // would let an administrator undo a revocation by re-entering an old key.
        if ($incoming->version < ($this->store->watermark()['version'] ?? 0)) {
            throw new ActivationRefused('would_roll_back');
        }

        try {
            $this->store->write($package->bytes, $package->envelope);
            $this->store->raiseWatermark($incoming->version, $incoming->status);
        } catch (StateNotWritable $e) {
            $this->logger->error('Product activation could not be stored: '.$e->getMessage());

            throw new ActivationRefused('not_writable', $e);
        }

        $this->gate->forget();
        $result = $this->gate->current();

        // A record that verified in flight but not from disk means the two disagree, and the
        // stored one is what every later request will read.
        if (!$result->granted) {
            throw new ActivationRefused($result->reason);
        }

        $this->logger->info(\sprintf('Product %s applied version %d.', $operation, $result->version()));

        return $result;
    }
}

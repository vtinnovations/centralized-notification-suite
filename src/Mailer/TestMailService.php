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

namespace VTInnovations\CentralizedNotificationSuite\Mailer;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Email;

/**
 * Sends one mail through a DSN that has not been saved yet.
 *
 * Built by hand rather than through Contao's mailer: the point is to prove the credentials
 * the operator just typed, before they are written to .env.local. Going through the
 * configured mailer would test the old settings and queue the message besides.
 *
 * Ported from vtinnovations/smtp-bundle (LGPL-3.0-or-later, VT Innovations Team).
 */
class TestMailService
{
    /**
     * Two attempts, because plenty of mail servers -- IONOS among them -- accept only one
     * connection at a time and stall the handshake of a second one instead of refusing it.
     * That surfaces as "Failed to enable crypto" on credentials which are perfectly valid, so
     * a single attempt would report a configuration error that does not exist.
     */
    private const ATTEMPTS = 2;

    /**
     * Seconds between attempts, to let whatever held the previous connection finish.
     */
    private const RETRY_DELAY = 3;

    /**
     * Shorter than PHP's default_socket_timeout (usually 60): a stalled handshake should cost
     * the operator a few seconds, not leave the backend hanging.
     */
    private const TIMEOUT = 12.0;

    public function sendTest(string $dsn, string $fromEmail, string $toEmail): TestResult
    {
        $start = microtime(true);
        $lastError = null;

        for ($attempt = 1; $attempt <= self::ATTEMPTS; ++$attempt) {
            try {
                $transport = Transport::fromDsn($dsn);

                if ($transport instanceof SmtpTransport && ($stream = $transport->getStream()) instanceof SocketStream) {
                    $stream->setTimeout(self::TIMEOUT);
                }

                $email = (new Email())
                    ->from($fromEmail)
                    ->to($toEmail)
                    ->subject('SMTP test')
                    ->text('Test e-mail sent from the Contao backend to verify the SMTP configuration.')
                ;

                (new Mailer($transport))->send($email);

                return new TestResult(true, null, microtime(true) - $start);
            } catch (\Throwable $e) {
                // Reported, never thrown: a wrong password is an answer, not a crash.
                $lastError = $e->getMessage();

                if ($attempt < self::ATTEMPTS) {
                    sleep(self::RETRY_DELAY);
                }
            }
        }

        return new TestResult(false, $lastError, microtime(true) - $start);
    }
}

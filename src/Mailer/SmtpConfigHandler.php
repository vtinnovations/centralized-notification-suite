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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\System;
use VTInnovations\CentralizedNotificationSuite\Cache\CacheClearService;
use VTInnovations\CentralizedNotificationSuite\Dotenv\DotenvWriter;
use VTInnovations\CentralizedNotificationSuite\Exception\CacheClearException;
use VTInnovations\CentralizedNotificationSuite\Exception\DotenvWriteException;
use VTInnovations\CentralizedNotificationSuite\Exception\InvalidDsnException;

/**
 * Validates SMTP settings, proves them with a test mail, then persists them.
 *
 * The order matters: nothing is written until a real message has gone through the credentials.
 * Saving first would leave a site whose every notification fails until someone notices.
 *
 * Ported from vtinnovations/smtp-bundle (LGPL-3.0-or-later, VT Innovations Team), with the
 * licence gating removed.
 */
class SmtpConfigHandler
{
    public const ENV_KEY = 'MAILER_DSN';

    public function __construct(
        private readonly DsnBuilder $dsnBuilder,
        private readonly TestMailService $testMailService,
        private readonly DotenvWriter $dotenvWriter,
        private readonly CacheClearService $cacheClearService,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly string $csrfTokenName,
    ) {
    }

    /**
     * The value for the REQUEST_TOKEN field Contao expects in every backend POST.
     */
    public function getRequestTokenValue(): string
    {
        return $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();
    }

    /**
     * @param array{host?: string, port?: int, encryption?: string, username?: string, password?: string, from_email?: string, test_recipient?: string} $data
     */
    public function handle(array $data): HandleResult
    {
        $host = trim($data['host'] ?? '');
        $port = (int) ($data['port'] ?? 587);
        $encryption = $data['encryption'] ?? 'tls';
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $fromEmail = trim($data['from_email'] ?? '');
        $testRecipient = trim($data['test_recipient'] ?? '');

        if ('' === $host) {
            return new HandleResult(false, $this->trans('error_host_required'));
        }

        if ('' === $fromEmail || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return new HandleResult(false, $this->trans('error_from_email_invalid'));
        }

        if ('' === $testRecipient || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            return new HandleResult(false, $this->trans('error_test_recipient_invalid'));
        }

        // Checked before the test mail, not after: sending proves credentials we would then be
        // unable to persist, and on a throttled mail server that wasted connection is the one
        // the operator needed.
        if (!$this->dotenvWriter->isWritable()) {
            return new HandleResult(false, $this->trans('error_env_not_writable', [
                '%path%' => $this->dotenvWriter->getPath(),
            ]));
        }

        // Blank password means "keep the one already saved", so host or port can be corrected
        // without the operator having to look the password up again.
        if ('' === $password) {
            $password = $this->extractExistingPassword($username);
        }

        try {
            $dsn = $this->dsnBuilder->build($host, $port, $username, $password, $encryption);
        } catch (InvalidDsnException $e) {
            return new HandleResult(false, $this->trans('error_invalid_config', ['%error%' => $e->getMessage()]));
        }

        $test = $this->testMailService->sendTest($dsn, $fromEmail, $testRecipient);

        if (!$test->success) {
            return new HandleResult(false, $this->trans('error_test_mail_failed', [
                '%duration%' => number_format($test->duration, 2),
                '%error%' => (string) $test->error,
            ]));
        }

        try {
            $this->dotenvWriter->write(self::ENV_KEY, $dsn);
        } catch (DotenvWriteException $e) {
            return new HandleResult(false, $this->trans('error_save_failed', ['%error%' => $e->getMessage()]));
        }

        try {
            $this->cacheClearService->clearAndWarmup();
        } catch (CacheClearException $e) {
            // Saved but not live yet: say so precisely rather than reporting a failure that
            // would have the operator re-enter settings which are in fact already correct.
            return new HandleResult(false, $this->trans('error_cache_clear_failed', ['%error%' => $e->getMessage()]));
        }

        return new HandleResult(true, $this->trans('success_config_saved', [
            '%duration%' => number_format($test->duration, 2),
        ]));
    }

    public function isConfigured(): bool
    {
        return null !== $this->dotenvWriter->read(self::ENV_KEY);
    }

    /**
     * Parses the stored DSN back into form fields. The password is never returned: the field
     * stays blank and blank means "unchanged".
     *
     * @return array{host: string, port: int, encryption: string, username: string, password: string, from_email: string, test_recipient: string}
     */
    public function getCurrentConfig(): array
    {
        $defaults = [
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from_email' => '',
            'test_recipient' => '',
        ];

        $dsn = $this->dotenvWriter->read(self::ENV_KEY);

        if (null === $dsn || false === ($parsed = parse_url($dsn))) {
            return $defaults;
        }

        $query = [];
        parse_str($parsed['query'] ?? '', $query);

        $encryption = match (true) {
            'smtps' === ($parsed['scheme'] ?? 'smtp') => 'ssl',
            'tls' === ($query['encryption'] ?? '') => 'tls',
            default => 'none',
        };

        return [
            ...$defaults,
            'host' => rawurldecode($parsed['host'] ?? ''),
            'port' => isset($parsed['port']) ? (int) $parsed['port'] : $defaults['port'],
            'encryption' => $encryption,
            'username' => rawurldecode($parsed['user'] ?? ''),
        ];
    }

    private function extractExistingPassword(string $username): string
    {
        $existing = $this->dotenvWriter->read(self::ENV_KEY);

        if (null === $existing || '' === $username) {
            return '';
        }

        $parsed = parse_url($existing);

        return isset($parsed['pass']) ? rawurldecode($parsed['pass']) : '';
    }

    /**
     * @param array<string, string> $params
     */
    private function trans(string $key, array $params = []): string
    {
        System::loadLanguageFile('notification_mailer');

        return strtr((string) ($GLOBALS['TL_LANG']['notification_mailer'][$key] ?? $key), $params);
    }
}

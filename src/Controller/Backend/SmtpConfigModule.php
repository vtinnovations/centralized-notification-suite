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

namespace VTInnovations\CentralizedNotificationSuite\Controller\Backend;

use Contao\BackendUser;
use Contao\System;
use VTInnovations\CentralizedNotificationSuite\Mailer\SmtpConfigHandler;

/**
 * The "Mailer" backend module: SMTP settings for the whole site.
 *
 * Registered as a BE_MOD callback, which Contao instantiates with `new ClassName()` -- hence
 * the services being pulled from the container rather than injected.
 *
 * Admins only: this writes credentials into .env.local and restarts the cache, which is not
 * something a content editor with backend access should be able to do.
 *
 * Ported from vtinnovations/smtp-bundle (LGPL-3.0-or-later, VT Innovations Team), with the
 * licence gating and remote provisioning removed.
 */
class SmtpConfigModule
{
    private const FORM_ID = 'notification_mailer';

    public function generate(): string
    {
        System::loadLanguageFile('notification_mailer');

        if (!BackendUser::getInstance()->isAdmin) {
            return $this->error($this->trans('access_denied'));
        }

        /** @var SmtpConfigHandler $handler */
        $handler = System::getContainer()->get(SmtpConfigHandler::class);

        $message = '';
        $isConfigured = $handler->isConfigured();
        $formData = $handler->getCurrentConfig();

        // Contao has already validated REQUEST_TOKEN by the time this runs
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && self::FORM_ID === ($_POST['FORM_SUBMIT'] ?? '')) {
            $formData = $this->extractPost();
            $result = $handler->handle($formData);
            $message = $result->success ? $this->confirm($result->message) : $this->error($result->message);

            if ($result->success) {
                $isConfigured = true;
                $formData = $handler->getCurrentConfig();
            }
        }

        return $this->renderForm($handler->getRequestTokenValue(), $message, $formData, $isConfigured);
    }

    /**
     * @return array{host: string, port: int, encryption: string, username: string, password: string, from_email: string, test_recipient: string}
     */
    private function extractPost(): array
    {
        return [
            'host' => trim($_POST['host'] ?? ''),
            'port' => max(1, min(65535, (int) ($_POST['port'] ?? 587))),
            'encryption' => $_POST['encryption'] ?? 'tls',
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'from_email' => trim($_POST['from_email'] ?? ''),
            'test_recipient' => trim($_POST['test_recipient'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(string $csrf, string $message, array $data, bool $isConfigured): string
    {
        $host = $this->e((string) ($data['host'] ?? ''));
        $port = (int) ($data['port'] ?? 587);
        $username = $this->e((string) ($data['username'] ?? ''));
        $fromEmail = $this->e((string) ($data['from_email'] ?? ''));
        $testRecipient = $this->e((string) ($data['test_recipient'] ?? ''));
        $currentEnc = (string) ($data['encryption'] ?? 'tls');

        $encOptions = '';

        foreach (['none' => 'encryption_none', 'tls' => 'encryption_starttls', 'ssl' => 'encryption_ssl'] as $value => $key) {
            $encOptions .= '<option value="'.$this->e($value).'"'.($currentEnc === $value ? ' selected' : '').'>'
                .$this->e($this->trans($key)).'</option>';
        }

        $statusBadge = $isConfigured
            ? '<span style="color:#5cb85c;font-weight:bold">&#10003; '.$this->e($this->trans('active')).'</span>'
            : '<span style="color:#d9534f">&#10007; '.$this->e($this->trans('not_configured')).'</span>';

        $t = fn (string $key): string => $this->e($this->trans($key));

        $back = $t('back');
        $headline = $t('headline');
        $intro = $t('intro');
        $serverSection = $t('server_section');
        $smtpHostLabel = $t('smtp_host_label');
        $portLabel = $t('port_label');
        $encryptionLabel = $t('encryption_label');
        $usernameLabel = $t('username_label');
        $passwordLabel = $t('password_label');
        $passwordHelp = $t('password_help');
        $emailSection = $t('email_section');
        $fromEmailLabel = $t('from_email_label');
        $testRecipientLabel = $t('test_recipient_label');
        $testRecipientHelp = $t('test_recipient_help');
        $saveBtn = $t('save_btn');
        $csrfEscaped = $this->e($csrf);

        return <<<HTML
            <div id="tl_buttons">
                <a href="contao" class="header_back" title="{$back}">{$back}</a>
            </div>

            <h2 class="sub_headline">{$headline} &nbsp;{$statusBadge}</h2>

            <div class="tl_formbody_edit">

                {$message}

                <p class="tl_message tl_info">{$intro}</p>

                <form method="post" id="notification_mailer_form" data-turbo="false">
                    <input type="hidden" name="REQUEST_TOKEN" value="{$csrfEscaped}">
                    <input type="hidden" name="FORM_SUBMIT" value="notification_mailer">

                    <fieldset class="tl_tbox block">
                        <legend onclick="AjaxRequest.toggleFieldset(this,'nm_server','')">{$serverSection}</legend>
                        <div id="nm_server">

                            <div class="widget w50">
                                <h3><label for="host">{$smtpHostLabel} <span class="mandatory">*</span></label></h3>
                                <input type="text" id="host" name="host" value="{$host}" class="tl_text" required placeholder="mail.example.com">
                            </div>

                            <div class="widget w50 w50x">
                                <h3><label for="port">{$portLabel}</label></h3>
                                <input type="number" id="port" name="port" value="{$port}" class="tl_text" min="1" max="65535">
                            </div>

                            <div class="widget w50">
                                <h3><label for="encryption">{$encryptionLabel}</label></h3>
                                <select id="encryption" name="encryption" class="tl_select">{$encOptions}</select>
                            </div>

                            <div class="widget w50 w50x">
                                <h3><label for="username">{$usernameLabel}</label></h3>
                                <input type="text" id="username" name="username" value="{$username}" class="tl_text" autocomplete="off">
                            </div>

                            <div class="widget w50">
                                <h3><label for="password">{$passwordLabel}</label></h3>
                                <input type="password" id="password" name="password" value="" class="tl_text" autocomplete="new-password">
                            </div>

                            <div class="widget w50 w50x">
                                <p class="tl_help tl_tip" style="margin-top:28px">{$passwordHelp}</p>
                            </div>

                        </div>
                    </fieldset>

                    <fieldset class="tl_tbox block">
                        <legend onclick="AjaxRequest.toggleFieldset(this,'nm_mail','')">{$emailSection}</legend>
                        <div id="nm_mail">

                            <div class="widget w50">
                                <h3><label for="from_email">{$fromEmailLabel} <span class="mandatory">*</span></label></h3>
                                <input type="email" id="from_email" name="from_email" value="{$fromEmail}" class="tl_text" required>
                            </div>

                            <div class="widget w50 w50x">
                                <h3><label for="test_recipient">{$testRecipientLabel} <span class="mandatory">*</span></label></h3>
                                <input type="email" id="test_recipient" name="test_recipient" value="{$testRecipient}" class="tl_text" required>
                                <p class="tl_help tl_tip">{$testRecipientHelp}</p>
                            </div>

                        </div>
                    </fieldset>

                    <div class="tl_formbody_submit">
                        <div class="tl_submit_container">
                            <button type="submit" class="tl_submit" accesskey="s">{$saveBtn}</button>
                        </div>
                    </div>

                </form>
            </div>
            HTML;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function error(string $message): string
    {
        return '<p class="tl_error">'.$this->e($message).'</p>';
    }

    private function confirm(string $message): string
    {
        return '<p class="tl_confirm">'.$this->e($message).'</p>';
    }

    private function trans(string $key): string
    {
        return (string) ($GLOBALS['TL_LANG']['notification_mailer'][$key] ?? $key);
    }
}

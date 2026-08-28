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

use Contao\BackendUser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use VTInnovations\CentralizedNotificationSuite\Message\MessageRenderer;
use VTInnovations\CentralizedNotificationSuite\Message\PreparedMessage;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\CentralizedNotificationSuite;
use VTInnovations\CentralizedNotificationSuite\Token\TokenRegistry;

/**
 * Sends one message to a chosen address with token values typed in by hand.
 *
 * The alternative is triggering the real event to see whether a notification works, which
 * on a live site means submitting a real enquiry or registering a fake member. The form
 * lists only the tokens this message actually uses, so it stays short.
 */
class TestSendController extends AbstractController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly MessageRenderer $renderer,
        private readonly TokenRegistry $tokens,
        private readonly CentralizedNotificationSuite $notifyCenter,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly Security $security,
        private readonly string $csrfTokenName,
    ) {
    }

    #[Route(
        path: '/contao/notification/test-send/{id}',
        name: 'centralized_notification_suite_test_send',
        requirements: ['id' => '\d+'],
        defaults: ['_scope' => 'backend', '_token_check' => false],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'notification');
        $this->framework->initialize();

        $message = MessageModel::findByPk($id);

        if (!$message) {
            return new Response('This message no longer exists.', Response::HTTP_NOT_FOUND);
        }

        $notification = NotificationModel::findByPk($message->pid);
        $type = (string) ($notification?->type ?? NotificationModel::TYPE_CUSTOM);
        $used = $this->findUsedTokens($message, $type);

        $user = $this->security->getUser();
        $defaultRecipient = $user instanceof BackendUser ? (string) $user->email : '';

        if (!$request->isMethod('POST')) {
            return new Response($this->form($id, $message, $used, $defaultRecipient, null, null));
        }

        if (!$this->tokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, (string) $request->request->get('rt')))) {
            return new Response('Invalid request token.', Response::HTTP_FORBIDDEN);
        }

        $recipient = trim((string) $request->request->get('recipient'));

        if ('' === $recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return new Response($this->form($id, $message, $used, $recipient, null, 'invalidRecipient'));
        }

        return new Response($this->send($request, $message, $notification, $type, $used, $recipient, $id));
    }

    /**
     * @param array<string, string> $used
     */
    private function send(
        Request $request,
        MessageModel $message,
        NotificationModel|null $notification,
        string $type,
        array $used,
        string $recipient,
        int $id,
    ): string {
        $values = [];

        foreach (array_keys($used) as $token) {
            $values[$token] = (string) $request->request->get('token_'.$token, '');
        }

        // Render from a copy with the recipient overridden, so a test can never reach the
        // real recipients of a live notification -- CC and BCC included.
        $copy = clone $message;
        $copy->recipients = $recipient;
        $copy->cc = '';
        $copy->bcc = '';

        $rendered = $this->renderer->render(
            $copy,
            (string) ($notification?->alias ?? ''),
            $this->tokens->withProvidedValues($values, $type),
        );

        $gateway = GatewayModel::findByPk($message->gateway);

        if (!$gateway || !$gateway->published) {
            return $this->form($id, $message, $used, $recipient, null, 'noGateway');
        }

        $sent = $this->notifyCenter->deliver(
            new PreparedMessage($rendered, $gateway),
            CentralizedNotificationSuite::SOURCE_TEST,
        );

        return $this->form($id, $message, $used, $recipient, $sent ? $recipient : null, $sent ? null : 'sendFailed');
    }

    /**
     * The tokens this message references, so the form asks for those and nothing else.
     *
     * @return array<string, string> Token name => description
     */
    private function findUsedTokens(MessageModel $message, string $type): array
    {
        $haystack = implode("\n", [
            (string) $message->subject,
            (string) $message->text,
            // The body the renderer will actually produce, not the raw html column: a
            // block-composed body keeps its tokens in the block records, where scanning the
            // column would never see them and the form would never ask for them.
            $this->renderer->resolveBody($message),
            (string) $message->recipients,
            // cc and bcc are token-parsed by render() but were never scanned, so a token in
            // a BCC address reached the recipient as a literal
            (string) $message->cc,
            (string) $message->bcc,
            (string) $message->reply_to,
        ]);

        preg_match_all('/##([^#=!<>\s][^=!<>\s]*?)##/', $haystack, $matches);

        $known = $this->tokens->getDefinitionsFor($type);

        // Values the bundle supplies itself (##host##, ##date##, ...) are filled in
        // automatically and must not be asked for.
        $provided = $this->tokens->withProvidedValues([], $type);
        $used = [];

        foreach (array_unique($matches[1] ?? []) as $token) {
            if (\array_key_exists($token, $provided)) {
                continue;
            }

            $used[$token] = $known[$token] ?? '';
        }

        ksort($used);

        return $used;
    }

    /**
     * @param array<string, string> $used
     */
    private function form(
        int $id,
        MessageModel $message,
        array $used,
        string $recipient,
        string|null $sentTo,
        string|null $error,
    ): string {
        $lang = $GLOBALS['TL_LANG']['tl_notification_message'] ?? [];
        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $labels = [
            'heading' => $lang['testSendHeading'] ?? 'Send a test message',
            'intro' => $lang['testSendIntro'] ?? 'Fill in the tokens this message uses and send it to yourself.',
            'recipient' => $lang['testSendRecipient'] ?? 'Send to',
            'submit' => $lang['testSendSubmit'] ?? 'Send test',
            'noTokens' => $lang['testSendNoTokens'] ?? 'This message uses no tokens you need to fill in.',
            'ok' => $lang['testSendOk'] ?? 'Test message sent to %s. Check the send log for the delivery result.',
            'invalidRecipient' => $lang['testSendInvalidRecipient'] ?? 'Enter a valid email address.',
            'noGateway' => $lang['testSendNoGateway'] ?? 'This message has no published sender, so it cannot be sent.',
            'sendFailed' => $lang['testSendFailed'] ?? 'Sending failed. The send log has the reason.',
        ];

        $notice = '';

        if (null !== $sentTo) {
            $notice = '<p class="sn-ok">'.$e(\sprintf($labels['ok'], $sentTo)).'</p>';
        } elseif (null !== $error) {
            $notice = '<p class="sn-error">'.$e($labels[$error] ?? $error).'</p>';
        }

        $fields = '';

        foreach ($used as $token => $description) {
            $fields .= \sprintf(
                '<p class="sn-field"><label for="t-%1$s"><code>##%2$s##</code>%3$s</label>'
                .'<input type="text" id="t-%1$s" name="token_%2$s" value=""></p>',
                $e(preg_replace('/[^a-z0-9_-]/i', '-', $token) ?? ''),
                $e($token),
                '' !== $description ? ' <span>'.$e($description).'</span>' : '',
            );
        }

        if ('' === $fields) {
            $fields = '<p class="sn-note">'.$e($labels['noTokens']).'</p>';
        }

        $action = $this->generateUrl('centralized_notification_suite_test_send', ['id' => $id]);
        $rt = $e($this->tokenManager->getDefaultTokenValue());

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>{$e($labels['heading'])}</title>
            <style>
              *,*::before,*::after { box-sizing:border-box; }
              body { margin:0; padding:20px; background:#eceef1; font:14px/1.55 -apple-system,"Segoe UI",Arial,sans-serif; color:#24292f; }
              .sn-box { max-width:640px; margin:0 auto; background:#fff; border:1px solid #d8dbdf; border-radius:6px; padding:22px; }
              h1 { margin:0 0 6px; font-size:17px; }
              .sn-intro { margin:0 0 18px; color:#6a737d; }
              .sn-field { margin:0 0 14px; }
              label { display:block; margin-bottom:4px; }
              label span { color:#6a737d; font-size:12px; }
              code { background:#f6f8fa; border:1px solid #e3e5e8; border-radius:3px; padding:1px 5px; font:12px ui-monospace,Menlo,Consolas,monospace; }
              input[type=text],input[type=email] { width:100%; font:inherit; padding:7px 9px; border:1px solid #c9ced4; border-radius:4px; }
              button { font:inherit; font-weight:600; cursor:pointer; margin-top:6px; padding:9px 20px; border:0; border-radius:4px; background:#24292f; color:#fff; }
              hr { border:0; border-top:1px solid #e6e8eb; margin:20px 0; }
              .sn-ok { background:#e8f6ec; border:1px solid #b7e0c4; color:#1a6b34; padding:10px 12px; border-radius:4px; margin:0 0 16px; }
              .sn-error { background:#fdeceb; border:1px solid #f3c0bd; color:#a02620; padding:10px 12px; border-radius:4px; margin:0 0 16px; }
              .sn-note { color:#6a737d; margin:0 0 14px; }
            </style>
            </head>
            <body>
            <div class="sn-box">
              <h1>{$e($labels['heading'])}</h1>
              <p class="sn-intro">{$e($labels['intro'])}</p>
              {$notice}
              <form method="post" action="{$e($action)}">
                <input type="hidden" name="rt" value="{$rt}">
                <p class="sn-field">
                  <label for="sn-recipient">{$e($labels['recipient'])}</label>
                  <input type="email" id="sn-recipient" name="recipient" value="{$e($recipient)}" required>
                </p>
                <hr>
                {$fields}
                <button type="submit">{$e($labels['submit'])}</button>
              </form>
            </div>
            </body>
            </html>
            HTML;
    }
}

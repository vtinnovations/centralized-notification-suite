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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use VTInnovations\CentralizedNotificationSuite\Message\Attachment;
use VTInnovations\CentralizedNotificationSuite\Message\MessageRenderer;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Token\TokenRegistry;

/**
 * Renders a message the way a recipient would receive it -- through the full pipeline,
 * including CSS inlining -- so an editor can see the finished mail without sending one.
 *
 * Token values are stand-ins derived from the token reference, because a preview has no
 * real submission behind it. That is enough to check layout, spacing and whether the CSS
 * survived inlining, which is what actually goes wrong with e-mail markup.
 */
class MessagePreviewController extends AbstractController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly MessageRenderer $renderer,
        private readonly TokenRegistry $tokens,
    ) {
    }

    #[Route(
        path: '/contao/notification/preview/{id}',
        name: 'centralized_notification_suite_preview',
        requirements: ['id' => '\d+'],
        defaults: ['_scope' => 'backend', '_token_check' => false],
        methods: ['GET'],
    )]
    public function __invoke(int $id): Response
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'notification');
        $this->framework->initialize();

        $message = MessageModel::findByPk($id);

        if (!$message) {
            return new Response('This message no longer exists.', Response::HTTP_NOT_FOUND);
        }

        $notification = NotificationModel::findByPk($message->pid);
        $type = (string) ($notification?->type ?? NotificationModel::TYPE_CUSTOM);

        $rendered = $this->renderer->render(
            $message,
            (string) ($notification?->alias ?? ''),
            $this->tokens->withProvidedValues($this->sampleTokens($type), $type),
        );

        // The message body is shown inside a sandboxed iframe, so a stray <script> in a
        // pasted design cannot run against the backend session.
        return new Response($this->page(
            $rendered->subject,
            $this->inlineCidImages($rendered->html, $rendered->attachments),
            $rendered->text,
        ));
    }

    /**
     * Swaps cid: references for data: URIs.
     *
     * "Embed images" turns a logo into an inline attachment referenced as <img src="cid:...">,
     * which is exactly right in a mail client and meaningless in a browser -- so without this
     * the preview shows a broken image for a message that arrives perfectly. Embedding the
     * bytes keeps the preview faithful and works whether or not the file is web-accessible.
     *
     * @param list<Attachment> $attachments
     */
    private function inlineCidImages(string|null $html, array $attachments): string|null
    {
        if (null === $html || !str_contains($html, 'cid:')) {
            return $html;
        }

        $replacements = [];

        foreach ($attachments as $attachment) {
            if (!$attachment->isInline() || !$attachment->isReadable()) {
                continue;
            }

            if (false === ($contents = $attachment->getContents())) {
                continue;
            }

            $replacements['cid:'.$attachment->cid] = \sprintf(
                'data:%s;base64,%s',
                $attachment->type ?: 'application/octet-stream',
                base64_encode($contents),
            );
        }

        return $replacements ? str_replace(array_keys($replacements), array_values($replacements), $html) : $html;
    }

    /**
     * Placeholder values for every documented token, so nothing renders as a literal
     * ##token## and the preview shows realistic line lengths.
     *
     * @return array<string, string>
     */
    private function sampleTokens(string $type): array
    {
        $samples = [];

        foreach (array_keys($this->tokens->getDefinitionsFor($type)) as $token) {
            // Documented families (##<field name>##) are not real token names
            if (str_contains($token, '<')) {
                continue;
            }

            $samples[$token] = \sprintf('[%s]', $token);
        }

        $samples['all_fields_html'] = $this->sampleTable();
        $samples['all_fields_filled_html'] = $samples['all_fields_html'];
        $samples['all_fields'] = "Name: Jane Example\nEmail: jane@example.com\nMessage: Sample message text";
        $samples['all_fields_filled'] = $samples['all_fields'];

        return $samples;
    }

    private function sampleTable(): string
    {
        return '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 16px">'
            .'<tr><th style="text-align:left;padding:7px 10px;border:1px solid #e3e5e8;background:#f6f8fa;font-size:13px;color:#57606a">Name</th>'
            .'<td style="padding:7px 10px;border:1px solid #e3e5e8;font-size:14px">Jane Example</td></tr>'
            .'<tr><th style="text-align:left;padding:7px 10px;border:1px solid #e3e5e8;background:#f6f8fa;font-size:13px;color:#57606a">Email</th>'
            .'<td style="padding:7px 10px;border:1px solid #e3e5e8;font-size:14px">jane@example.com</td></tr>'
            .'</table>';
    }

    /**
     * A minimal chrome around the message: subject line, a desktop/mobile width toggle and
     * a tab for the plain text part, which is otherwise never seen until someone complains.
     */
    private function page(string $subject, string|null $html, string $text): string
    {
        $lang = $GLOBALS['TL_LANG']['tl_notification_message'] ?? [];
        $labels = [
            'subject' => $lang['previewSubject'] ?? 'Subject',
            'desktop' => $lang['previewDesktop'] ?? 'Desktop',
            'mobile' => $lang['previewMobile'] ?? 'Mobile',
            'htmlTab' => $lang['previewHtml'] ?? 'HTML',
            'textTab' => $lang['previewText'] ?? 'Plain text',
            'noHtml' => $lang['previewNoHtml'] ?? 'This message has no HTML part.',
            'sample' => $lang['previewSample'] ?? 'Token values are samples, not real data.',
        ];

        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $htmlPart = null !== $html && '' !== trim($html)
            ? '<iframe id="sn-html" sandbox="allow-same-origin" srcdoc="'.$e($html).'"></iframe>'
            : '<p class="sn-empty">'.$e($labels['noHtml']).'</p>';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>{$e($subject)}</title>
            <style>
              *,*::before,*::after { box-sizing: border-box; }
              body { margin:0; background:#eceef1; font:14px/1.5 -apple-system,"Segoe UI",Arial,sans-serif; color:#24292f; }
              header { background:#fff; border-bottom:1px solid #d8dbdf; padding:14px 18px; }
              .sn-subject { font-size:16px; font-weight:600; margin:0 0 3px; word-break:break-word; }
              .sn-meta { color:#6a737d; font-size:12px; margin:0; }
              nav { display:flex; gap:6px; flex-wrap:wrap; padding:10px 18px; background:#f6f7f9; border-bottom:1px solid #d8dbdf; }
              button { font:inherit; cursor:pointer; border:1px solid #c9ced4; background:#fff; border-radius:4px; padding:5px 12px; color:#24292f; }
              button[aria-pressed="true"] { background:#24292f; border-color:#24292f; color:#fff; }
              main { padding:18px; }
              .sn-stage { margin:0 auto; transition:max-width .15s ease; }
              .sn-stage.desktop { max-width:100%; }
              .sn-stage.mobile { max-width:390px; }
              iframe { width:100%; min-height:70vh; border:1px solid #d8dbdf; background:#fff; display:block; }
              pre { margin:0; padding:18px; background:#fff; border:1px solid #d8dbdf; white-space:pre-wrap; word-break:break-word; font:13px/1.6 ui-monospace,Menlo,Consolas,monospace; }
              .sn-empty { padding:18px; background:#fff; border:1px solid #d8dbdf; color:#6a737d; }
              [hidden] { display:none !important; }
            </style>
            </head>
            <body>
            <header>
              <p class="sn-subject">{$e($subject)}</p>
              <p class="sn-meta">{$e($labels['subject'])} &middot; {$e($labels['sample'])}</p>
            </header>
            <nav>
              <button type="button" data-tab="html" aria-pressed="true">{$e($labels['htmlTab'])}</button>
              <button type="button" data-tab="text" aria-pressed="false">{$e($labels['textTab'])}</button>
              <span style="flex:1"></span>
              <button type="button" data-width="desktop" aria-pressed="true">{$e($labels['desktop'])}</button>
              <button type="button" data-width="mobile" aria-pressed="false">{$e($labels['mobile'])}</button>
            </nav>
            <main>
              <div class="sn-stage desktop" id="sn-stage">
                <div data-pane="html">{$htmlPart}</div>
                <div data-pane="text" hidden><pre>{$e($text)}</pre></div>
              </div>
            </main>
            <script>
            (function () {
              var stage = document.getElementById('sn-stage');
              function group(attr, apply) {
                var buttons = document.querySelectorAll('button[' + attr + ']');
                buttons.forEach(function (button) {
                  button.addEventListener('click', function () {
                    buttons.forEach(function (b) { b.setAttribute('aria-pressed', String(b === button)); });
                    apply(button.getAttribute(attr));
                  });
                });
              }
              group('data-tab', function (name) {
                document.querySelectorAll('[data-pane]').forEach(function (pane) {
                  pane.hidden = pane.getAttribute('data-pane') !== name;
                });
              });
              group('data-width', function (name) { stage.className = 'sn-stage ' + name; });
            })();
            </script>
            </body>
            </html>
            HTML;
    }
}

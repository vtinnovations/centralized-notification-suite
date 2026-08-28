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

namespace VTInnovations\CentralizedNotificationSuite\Message;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use VTInnovations\CentralizedNotificationSuite\Block\BlockBodyRenderer;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\TemplateModel;

/**
 * Turns a stored message into a RenderedMessage: ##tokens## first, then {{insert::tags}}.
 *
 * Rendering is deliberately separate from sending so that the backend preview, the test
 * send and the send log can all produce or replay exactly what a recipient would get
 * without a gateway being involved.
 */
class MessageRenderer
{
    /**
     * Token values are escaped before they enter an HTML body, because most of them come
     * from untrusted input (a form field, a member's name) and would otherwise be able to
     * inject markup into the e-mail or simply break its layout with a stray "&".
     *
     * Tokens whose name ends in this suffix are exempt: they are built by the bundle
     * itself and are HTML by definition (##all_fields_html##, ##uploads_html##, ...).
     * The convention is deliberate -- a template author can tell at a glance which tokens
     * carry markup, and a token provider can add one without registering it anywhere.
     */
    public const RAW_HTML_TOKEN_SUFFIX = '_html';

    /**
     * tl_notification_template.layout_mode value for a generated design.
     */
    public const MODE_DESIGN = 'design';

    public function __construct(
        private readonly SimpleTokenParser $tokenParser,
        private readonly InsertTagParser $insertTagParser,
        private readonly AttachmentResolver $attachmentResolver,
        private readonly HtmlRenderer $htmlRenderer,
        private readonly PlainTextConverter $plainTextConverter,
        private readonly ImageEmbedder $imageEmbedder,
        private readonly DesignLibrary $designs,
        private readonly BrandingProvider $branding,
        private readonly BlockBodyRenderer $blocks,
    ) {
    }

    /**
     * @param array<string, string> $tokens
     * @param list<Attachment>      $extraAttachments Added on top of the message's own configured attachments
     */
    public function render(MessageModel $message, string $alias, array $tokens, array $extraAttachments = []): RenderedMessage
    {
        [$html, $inlineImages] = $this->buildHtml($message, $tokens);

        return new RenderedMessage(
            messageId: (int) $message->id,
            notificationId: (int) $message->pid,
            alias: $alias,
            reference: bin2hex(random_bytes(8)),
            subject: $this->renderText((string) $message->subject, $tokens),
            text: $this->buildText($message, $tokens, $html),
            html: $html,
            recipients: $this->renderText((string) $message->recipients, $tokens),
            cc: $this->renderText((string) $message->cc, $tokens),
            bcc: $this->renderText((string) $message->bcc, $tokens),
            replyTo: $this->renderText((string) $message->reply_to, $tokens),
            priority: (int) ($message->priority ?: 3),
            attachments: [
                ...$this->attachmentResolver->resolveUuids($message->attachments),
                ...$extraAttachments,
                ...$inlineImages,
            ],
            tokens: $tokens,
        );
    }

    /**
     * Blocks -> body -> tokens -> layout -> inlined CSS -> embedded images.
     *
     * @param array<string, string> $tokens
     *
     * @return array{0: string|null, 1: list<Attachment>}
     */
    private function buildHtml(MessageModel $message, array $tokens): array
    {
        $body = $this->resolveBody($message);

        if ('' === trim($body)) {
            return [null, []];
        }

        $html = $this->htmlRenderer->render(
            $this->renderHtml($body, $tokens),
            $this->resolveLayout($message, $tokens),
        );

        if (!$message->embed_images) {
            return [$html, []];
        }

        return $this->imageEmbedder->embed($html);
    }

    /**
     * The message body before any token parsing: either the raw HTML field or the markup
     * composed from the message's blocks.
     *
     * Public because TestSendController scans it for ##tokens##, and scanning anything other
     * than what will actually be rendered is how a token goes silently missing from the
     * test-send form.
     */
    public function resolveBody(MessageModel $message): string
    {
        if (MessageModel::BODY_MODE_BLOCKS !== $message->body_mode) {
            return (string) $message->html;
        }

        return $this->blocks->renderBody((int) $message->id, $this->branding->get());
    }

    /**
     * Renders the template's own markup with the same tokens, so a header can greet the
     * recipient by name and a footer can carry an unsubscribe link.
     *
     * @param array<string, string> $tokens
     */
    private function resolveLayout(MessageModel $message, array $tokens): EmailLayout|null
    {
        if (!$message->template) {
            return null;
        }

        $template = TemplateModel::findByPk($message->template);

        if (!$template || !$template->published) {
            return null;
        }

        $preheader = $this->renderText((string) $template->preheader, $tokens);

        // A design layout is generated from the branding record rather than stored as markup,
        // so changing the logo or the brand colour updates every design at once.
        if (self::MODE_DESIGN === $template->layout_mode) {
            $design = $this->designs->build((string) $template->design, $this->branding->get());

            return new EmailLayout(
                mode: $design->mode,
                wrapperHtml: $this->renderTemplatePart($design->wrapperHtml, $tokens),
                css: $design->css,
                preheader: $preheader,
                inlineCss: $design->inlineCss,
            );
        }

        $rendered = ['preheader' => $preheader];

        foreach (['wrapper_html', 'header_html', 'footer_html'] as $field) {
            $rendered[$field] = $this->renderTemplatePart((string) $template->$field, $tokens);
        }

        // The rendered parts go into a value object rather than back onto the model, which
        // Contao shares through its registry and which must not end up holding one
        // recipient's content.
        return EmailLayout::fromModel($template, $rendered);
    }

    /**
     * Splits on the body placeholder before parsing, so the placeholder never reaches the
     * token parser and neither the template nor the body is ever parsed twice.
     *
     * @param array<string, string> $tokens
     */
    private function renderTemplatePart(string $markup, array $tokens): string
    {
        $segments = explode(TemplateModel::BODY_PLACEHOLDER, $markup);

        foreach ($segments as $i => $segment) {
            $segments[$i] = $this->renderHtml($segment, $tokens);
        }

        return implode(TemplateModel::BODY_PLACEHOLDER, $segments);
    }

    /**
     * @param array<string, string> $tokens
     */
    private function buildText(MessageModel $message, array $tokens, string|null $html): string
    {
        $text = $this->renderText((string) $message->text, $tokens);

        // Generate from the HTML when asked to, and also when there is simply nothing else
        // -- an HTML-only message would otherwise go out without a text alternative.
        if (null !== $html && ('' === $text || $message->auto_plaintext)) {
            return $this->plainTextConverter->convert($html);
        }

        return $text;
    }

    /**
     * Renders a value destined for an HTML context. Token values are escaped and their
     * line breaks converted to <br>, so a multi-line textarea reaches the recipient as
     * more than one run-on line -- except for ##*_html## tokens, which are inserted raw.
     *
     * @param array<string, string> $tokens
     */
    public function renderHtml(string $value, array $tokens): string
    {
        if ('' === $value) {
            return '';
        }

        return $this->insertTagParser->replaceInline(
            $this->tokenParser->parse($value, $this->escapeTokens($tokens)),
        );
    }

    /**
     * @param array<string, string> $tokens
     *
     * @return array<string, string>
     */
    private function escapeTokens(array $tokens): array
    {
        $escaped = [];

        foreach ($tokens as $name => $value) {
            if (str_ends_with($name, self::RAW_HTML_TOKEN_SUFFIX)) {
                $escaped[$name] = $value;

                continue;
            }

            $escaped[$name] = nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), false);
        }

        return $escaped;
    }

    /**
     * Renders a value destined for a plain-text context (subject, text body, address
     * fields). The insert tag parser escapes non-HTML output for safe HTML embedding, so
     * entities are decoded again afterwards -- otherwise an "&" in a submitted form field
     * would reach the recipient as "&amp;".
     *
     * @param array<string, string> $tokens
     */
    public function renderText(string $value, array $tokens): string
    {
        if ('' === $value) {
            return '';
        }

        $parsed = $this->insertTagParser->replaceInline($this->tokenParser->parse($value, $tokens, false));

        return html_entity_decode($parsed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

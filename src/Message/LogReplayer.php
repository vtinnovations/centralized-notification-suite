<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

use VTInnovations\SimpleNotifyBundle\Model\GatewayModel;
use VTInnovations\SimpleNotifyBundle\Model\LogModel;
use VTInnovations\SimpleNotifyBundle\Model\MessageModel;

/**
 * Rebuilds a sendable message from a send-log row.
 *
 * The stored body is replayed verbatim rather than re-rendered: the token values that
 * produced it (a form submission) are gone, and re-rendering would either fail or quietly
 * produce different content than the one the log claims was sent.
 *
 * One thing cannot be replayed: files uploaded through a form live in PHP's temp directory
 * and are deleted when the request ends. A resend therefore carries the message's own
 * configured attachments (stable file-system UUIDs) but not the visitor's upload.
 */
class LogReplayer
{
    public function __construct(private readonly AttachmentResolver $attachmentResolver)
    {
    }

    public function toPrepared(LogModel $log): PreparedMessage
    {
        $envelope = $this->decodeEnvelope($log);
        $gateway = GatewayModel::findByPk($log->gateway);

        $message = new RenderedMessage(
            messageId: (int) $log->message,
            notificationId: (int) $log->pid,
            alias: (string) $log->alias,
            // Same reference, so the replay updates this row instead of adding another
            reference: (string) $log->reference,
            subject: (string) $log->subject,
            text: (string) $log->body_text,
            html: $log->body_html,
            recipients: (string) $log->recipients,
            cc: (string) ($envelope['cc'] ?? ''),
            bcc: (string) ($envelope['bcc'] ?? ''),
            replyTo: (string) ($envelope['reply_to'] ?? ''),
            priority: (int) ($envelope['priority'] ?? 3),
            attachments: $this->resolveAttachments((int) $log->message),
            tokens: \is_array($envelope['tokens'] ?? null) ? $envelope['tokens'] : [],
        );

        return new PreparedMessage($message, $gateway, $this->findProblem($log, $gateway));
    }

    /**
     * True when there is enough stored content to replay at all -- store_body being off
     * means the log can report what happened but not repeat it.
     */
    public function isReplayable(LogModel $log): bool
    {
        return '' !== (string) $log->body_text || '' !== (string) $log->body_html;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeEnvelope(LogModel $log): array
    {
        if (null === $log->envelope || '' === $log->envelope) {
            return [];
        }

        try {
            $decoded = json_decode((string) $log->envelope, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<Attachment>
     */
    private function resolveAttachments(int $messageId): array
    {
        $message = MessageModel::findByPk($messageId);

        return $message ? $this->attachmentResolver->resolveUuids($message->attachments) : [];
    }

    private function findProblem(LogModel $log, GatewayModel|null $gateway): string|null
    {
        if (!$this->isReplayable($log)) {
            return 'the message body was not stored, so it cannot be re-sent';
        }

        if (!$gateway) {
            return 'the gateway it was sent through no longer exists';
        }

        if (!$gateway->published) {
            return \sprintf('the gateway "%s" is not published', $gateway->title);
        }

        return null;
    }
}

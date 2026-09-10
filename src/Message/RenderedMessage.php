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

/**
 * A tl_notification_message with every token and insert tag already resolved -- what a gateway
 * actually sends. Content properties are mutable on purpose: listeners on PreSendEvent
 * are meant to rewrite them (add a BCC archive address, prefix a staging subject, ...).
 */
class RenderedMessage
{
    /**
     * @param list<Attachment>      $attachments
     * @param array<string, string> $tokens      The token set this message was rendered from, kept for logging and resend
     */
    /**
     * @param string $reference Correlates this attempt with its send-log row. Contao
     *                          queues mail through Messenger, so the transport succeeds or
     *                          fails in a worker process long after send() returned; the
     *                          reference travels with the e-mail as a header and lets
     *                          MailerEventListener find the row again and record the real
     *                          outcome. Empty for messages that are never logged.
     */
    public function __construct(
        public readonly int $messageId,
        public readonly int $notificationId,
        public readonly string $alias,
        public readonly string $reference = '',
        public string $subject = '',
        public string $text = '',
        public string|null $html = null,
        public string $recipients = '',
        public string $cc = '',
        public string $bcc = '',
        public string $replyTo = '',
        public int $priority = 3,
        public array $attachments = [],
        public readonly array $tokens = [],
    ) {
    }

    /**
     * Things that went wrong without preventing delivery -- an attachment that could not be
     * read, an address that was rejected. The message still goes out, but the send log has
     * to say so: a mail that silently arrives without its attachment is the hardest kind of
     * problem to diagnose from the outside.
     *
     * @var list<string>
     */
    public array $warnings = [];

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Splits a comma/semicolon/newline separated address field into unique, trimmed values.
     * Gateways that do not deal in e-mail addresses (webhooks) can ignore these.
     *
     * @return list<string>
     */
    public function getRecipientList(): array
    {
        return self::splitAddressList($this->recipients);
    }

    /**
     * @return list<string>
     */
    public function getCcList(): array
    {
        return self::splitAddressList($this->cc);
    }

    /**
     * @return list<string>
     */
    public function getBccList(): array
    {
        return self::splitAddressList($this->bcc);
    }

    public function hasHtml(): bool
    {
        return null !== $this->html && '' !== trim($this->html);
    }

    /**
     * @return list<string>
     */
    public static function splitAddressList(string $value): array
    {
        $parts = preg_split('/[,;\r\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_filter(array_map('trim', $parts));

        return array_values(array_unique($parts));
    }
}

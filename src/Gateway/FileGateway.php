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

namespace VTInnovations\CentralizedNotificationSuite\Gateway;

use VTInnovations\CentralizedNotificationSuite\Message\RenderedMessage;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;

/**
 * Writes the message to a file instead of sending it.
 *
 * The point is to be able to build and check notifications on a local or staging site
 * without a mail server and without any chance of reaching a real recipient -- the usual
 * alternative being a live SMTP account and a lot of care. Combined with the send log it
 * also makes the exact bytes inspectable.
 */
class FileGateway extends AbstractGateway
{
    public const NAME = 'file';

    public function __construct(
        private readonly string $defaultDir,
        private readonly ActivationGate $activation,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            'file_dir' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['maxlength' => 512, 'decodeEntities' => true, 'tl_class' => 'long clr'],
                'sql' => "varchar(512) NOT NULL default ''",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{file_legend},file_dir';
    }

    /**
     * The destination is file_dir; the recipient fields are never read.
     */
    public function addressesRecipients(): bool
    {
        return false;
    }

    public function send(RenderedMessage $message, array $gatewayConfig): bool
    {
        // A second, independent check. The dispatcher already refuses to run unlicensed,
        // and this repeats the question at the point where a message would actually leave
        // the server -- so deleting or bypassing one service does not open every path.
        if (!$this->activation->current()->granted) {
            throw new \RuntimeException('This installation is not activated, so nothing was sent.');
        }

        $dir = $this->resolveDir((string) ($gatewayConfig['file_dir'] ?? ''));

        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Could not create the mail dump directory "%s".', $dir));
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException(\sprintf('The mail dump directory "%s" is not writable.', $dir));
        }

        // Reference in the filename so a send-log entry maps to its file at a glance
        $file = \sprintf('%s/%s-%s.eml', $dir, date('Ymd-His'), $message->reference ?: uniqid());

        if (false === file_put_contents($file, $this->format($message))) {
            throw new \RuntimeException(\sprintf('Could not write the message to "%s".', $file));
        }

        return true;
    }

    private function resolveDir(string $configured): string
    {
        $configured = trim($configured);

        if ('' === $configured) {
            return $this->defaultDir;
        }

        // Relative paths are resolved inside the project, so a gateway cannot be pointed at
        // an arbitrary location on the server through the backend.
        if (!str_starts_with($configured, '/')) {
            return $this->defaultDir.'/'.trim($configured, '/');
        }

        return $configured;
    }

    /**
     * A readable, RFC-ish dump rather than a real MIME message: the goal is for a developer
     * to open it and see what would have been sent.
     */
    private function format(RenderedMessage $message): string
    {
        $lines = [
            'Date: '.date('r'),
            'Subject: '.$message->subject,
            'To: '.implode(', ', $message->getRecipientList()),
        ];

        foreach (['Cc' => $message->getCcList(), 'Bcc' => $message->getBccList()] as $header => $addresses) {
            if ($addresses) {
                $lines[] = $header.': '.implode(', ', $addresses);
            }
        }

        if ('' !== $message->replyTo) {
            $lines[] = 'Reply-To: '.$message->replyTo;
        }

        $lines[] = 'X-Notification: '.$message->alias.' (message '.$message->messageId.')';
        $lines[] = 'X-Notification-Ref: '.$message->reference;

        foreach ($message->attachments as $attachment) {
            $lines[] = \sprintf(
                'X-Attachment: %s (%s%s)',
                $attachment->name,
                $attachment->type ?? 'unknown type',
                $attachment->isInline() ? ', inline cid:'.$attachment->cid : '',
            );
        }

        $body = ["\n--- text/plain ---\n", $message->text];

        if ($message->hasHtml()) {
            $body[] = "\n\n--- text/html ---\n";
            $body[] = (string) $message->html;
        }

        return implode("\n", $lines)."\n".implode('', $body)."\n";
    }
}

<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Gateway;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Part\DataPart;
use VTInnovations\SimpleNotifyBundle\Message\RenderedMessage;

class EmailGateway implements GatewayInterface
{
    public const NAME = 'email';

    /**
     * Carries the send-log correlation reference so MailerEventListener can record the
     * real delivery outcome once the Messenger worker has run. It travels with the message,
     * which also makes it a useful handle when tracing a single mail through server logs.
     */
    public const REFERENCE_HEADER = 'X-Simple-Notify-Ref';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            'sender_name' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
                'sql' => "varchar(255) NOT NULL default ''",
            ],
            'sender_email' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['mandatory' => true, 'rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50'],
                'sql' => "varchar(255) NOT NULL default ''",
            ],
            'reply_to' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50'],
                'sql' => "varchar(255) NOT NULL default ''",
            ],
            'mailer_transport' => [
                'exclude' => true,
                'inputType' => 'select',
                'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
                'sql' => "varchar(64) NOT NULL default ''",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{email_legend},sender_name,sender_email,reply_to,mailer_transport';
    }

    /**
     * Contao routes mail through a Messenger queue, so send() only enqueues. The real
     * outcome arrives later via MailerEventListener.
     */
    public function isAsynchronous(): bool
    {
        return true;
    }

    public function send(RenderedMessage $message, array $gatewayConfig): bool
    {
        $email = (new Email())
            ->subject($message->subject)
            ->from(new Address((string) $gatewayConfig['sender_email'], (string) $gatewayConfig['sender_name']))
            ->priority($this->normalisePriority($message->priority))
        ;

        if ('' !== $message->text) {
            $email->text($message->text);
        }

        if ($message->hasHtml()) {
            $email->html((string) $message->html);
        }

        // A message with neither part is a configuration error, not something to send:
        // most providers reject an empty body outright and the recipient learns nothing.
        if ('' === $message->text && !$message->hasHtml()) {
            throw new \RuntimeException(\sprintf('Message ID %d has neither a text nor an HTML body.', $message->messageId));
        }

        $this->addAddresses($email, 'addTo', $message->getRecipientList(), $message);
        $this->addAddresses($email, 'addCc', $message->getCcList(), $message);
        $this->addAddresses($email, 'addBcc', $message->getBccList(), $message);

        // Per-message reply-to wins over the gateway default
        $replyTo = '' !== $message->replyTo ? $message->replyTo : (string) ($gatewayConfig['reply_to'] ?? '');

        if ('' !== $replyTo) {
            $this->addAddresses($email, 'addReplyTo', RenderedMessage::splitAddressList($replyTo), $message);
        }

        // Every recipient was invalid or the field rendered empty -- sending would throw
        // inside the transport, so fail here with a message that says why.
        if (!$email->getTo()) {
            throw new \RuntimeException(\sprintf('Message ID %d has no valid recipient (rendered value: "%s").', $message->messageId, $message->recipients));
        }

        $this->attachFiles($email, $message);

        // Route through a named Symfony Mailer transport the same way Contao's
        // own tl_page/tl_form "mailerTransport" field does (see ContaoMailer::setTransport()).
        if (!empty($gatewayConfig['mailer_transport'])) {
            $email->getHeaders()->addTextHeader('X-Transport', (string) $gatewayConfig['mailer_transport']);
        }

        if ('' !== $message->reference) {
            $email->getHeaders()->addTextHeader(self::REFERENCE_HEADER, $message->reference);
        }

        $this->mailer->send($email);

        return true;
    }

    /**
     * Adds addresses one at a time so a single malformed value -- a typo in a recipient
     * list, or an ##email## token that rendered empty -- is skipped and logged instead of
     * aborting the whole message.
     *
     * @param list<string> $addresses
     */
    private function addAddresses(Email $email, string $method, array $addresses, RenderedMessage $message): void
    {
        foreach ($addresses as $address) {
            try {
                $email->$method(Address::create($address));
            } catch (RfcComplianceException $e) {
                $warning = \sprintf('The address "%s" is not valid and was skipped.', $address);

                $message->addWarning($warning);
                $this->logger->warning(\sprintf('%s (%s on message ID %d: %s)', $warning, $method, $message->messageId, $e->getMessage()));
            }
        }
    }

    private function attachFiles(Email $email, RenderedMessage $message): void
    {
        foreach ($message->attachments as $attachment) {
            if (!$attachment->isReadable()) {
                // Recorded on the message, not just in the log file: the send log is where
                // someone looks when a mail arrives without the file they expected.
                $warning = \sprintf('The attachment "%s" could not be read and was not sent.', $attachment->name);

                $message->addWarning($warning);
                $this->logger->warning(\sprintf('%s (message ID %d, path "%s")', $warning, $message->messageId, $attachment->path));

                continue;
            }

            $contents = $attachment->getContents();

            if (false === $contents) {
                continue;
            }

            // Read the bytes eagerly instead of using attachFromPath(): Contao queues mail
            // through Messenger, so a lazy path reference would break once the request ends
            // (form uploads live in PHP's temp dir and are deleted at shutdown).
            if ($attachment->isInline()) {
                $part = new DataPart($contents, $attachment->name, $attachment->type);
                $part->asInline();
                $part->setContentId((string) $attachment->cid);
                $email->addPart($part);

                continue;
            }

            $email->attach($contents, $attachment->name, $attachment->type);
        }
    }

    /**
     * Symfony accepts 1 (highest) to 5 (lowest); anything else would throw.
     */
    private function normalisePriority(int $priority): int
    {
        return max(Email::PRIORITY_HIGHEST, min(Email::PRIORITY_LOWEST, $priority));
    }
}

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

namespace VTInnovations\CentralizedNotificationSuite\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;
use VTInnovations\CentralizedNotificationSuite\Gateway\EmailGateway;
use VTInnovations\CentralizedNotificationSuite\Model\LogModel;
use VTInnovations\CentralizedNotificationSuite\SendResult;

/**
 * Records the *real* delivery outcome of an e-mail notification.
 *
 * Contao routes mail through Messenger, so MailerInterface::send() returns as soon as the
 * message is queued -- long before an SMTP server has seen it. Without this listener the
 * send log could only ever say "handed to the queue", which is exactly the kind of
 * half-answer that makes a delivery log useless when a customer says they got nothing.
 *
 * These events are dispatched by the transport, i.e. inside the worker process, so the
 * correlation reference that EmailGateway wrote into the message headers is the only link
 * back to the log row.
 */
class MailerEventListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly bool $enabled,
    ) {
    }

    #[AsEventListener]
    public function onSent(SentMessageEvent $event): void
    {
        $this->update($event->getMessage()->getOriginalMessage(), SendResult::STATUS_SENT, null);
    }

    #[AsEventListener]
    public function onFailed(FailedMessageEvent $event): void
    {
        $this->update($event->getMessage(), SendResult::STATUS_FAILED, $event->getError());
    }

    private function update(RawMessage $message, string $status, \Throwable|null $error): void
    {
        if (!$this->enabled) {
            return;
        }

        $reference = $this->extractReference($message);

        if (null === $reference) {
            return;
        }

        $this->framework->initialize();

        $log = LogModel::findByReference($reference);

        if (!$log) {
            return;
        }

        $log->status = $status;
        $log->tstamp = time();
        $log->last_attempt = time();

        if (null !== $error) {
            $log->error = $error->getMessage();
        }

        $log->save();
    }

    /**
     * A RawMessage carries no headers, only a serialised body -- only a Message subclass
     * (which Email is) exposes the reference header.
     */
    private function extractReference(RawMessage $message): string|null
    {
        if (!$message instanceof Message) {
            return null;
        }

        $header = $message->getHeaders()->get(EmailGateway::REFERENCE_HEADER);

        return $header ? ($header->getBodyAsString() ?: null) : null;
    }
}

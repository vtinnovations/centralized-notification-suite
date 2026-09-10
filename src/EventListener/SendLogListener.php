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
use VTInnovations\CentralizedNotificationSuite\Event\PostSendEvent;
use VTInnovations\CentralizedNotificationSuite\Event\PreSendEvent;
use VTInnovations\CentralizedNotificationSuite\Model\LogModel;
use VTInnovations\CentralizedNotificationSuite\SendResult;

/**
 * Writes the send log. Implemented as an event listener rather than inside
 * CentralizedNotificationSuite so that turning logging off removes the behaviour entirely instead of
 * branching around it, and so a project can add its own listener alongside.
 *
 * The row is created *before* the gateway is called and updated afterwards. That ordering
 * matters for two reasons: a process that dies mid-send still leaves evidence, and the
 * Messenger worker (which reports the real delivery outcome via MailerEventListener) can
 * find the row no matter whether the mail transport is sync or async.
 */
class SendLogListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly bool $enabled,
        private readonly bool $storeBody,
    ) {
    }

    #[AsEventListener]
    public function onPreSend(PreSendEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->framework->initialize();

        $message = $event->message;

        // A resend reuses the reference of the row it replays, so attempts accumulate on
        // one entry instead of the log filling with near-duplicates.
        $log = LogModel::findByReference($message->reference) ?? new LogModel();

        $log->reference = $message->reference;
        $log->tstamp = time();
        $log->last_attempt = time();
        $log->pid = $message->notificationId;
        $log->message = $message->messageId;
        $log->alias = $message->alias;
        $log->gateway = (int) ($event->gatewayConfig['id'] ?? 0);
        $log->gateway_type = $event->gatewayType;
        $log->recipients = $message->recipients;
        $log->subject = $message->subject;
        $log->status = SendResult::STATUS_PENDING;
        $log->error = null;
        $log->source = $event->source;
        $log->attempts = (int) $log->attempts + 1;

        if ($this->storeBody) {
            $log->body_text = $message->text;
            $log->body_html = $message->html;
            $log->envelope = json_encode([
                'cc' => $message->cc,
                'bcc' => $message->bcc,
                'reply_to' => $message->replyTo,
                'priority' => $message->priority,
                'tokens' => $message->tokens,
            ], JSON_THROW_ON_ERROR);
        }

        $log->save();
    }

    #[AsEventListener]
    public function onPostSend(PostSendEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->framework->initialize();

        $message = $event->message;
        $log = LogModel::findByReference($message->reference);

        // No pre-send row: the message never reached a gateway (unpublished, no gateway
        // registered), so PreSendEvent was never dispatched for it.
        if (!$log) {
            $log = new LogModel();
            $log->reference = $message->reference;
            $log->pid = $message->notificationId;
            $log->message = $message->messageId;
            $log->alias = $message->alias;
            $log->gateway = (int) ($event->gatewayConfig['id'] ?? 0);
            $log->gateway_type = $event->gatewayType;
            $log->recipients = $message->recipients;
            $log->subject = $message->subject;
            $log->source = $event->source;
            $log->attempts = 0;
        }

        $log->tstamp = time();
        $log->last_attempt = time();
        $log->error = $this->describe($event);

        if (!$event->isSuccessful()) {
            // "failed" and "skipped" are taken from the event rather than inferred, because
            // the retry cron re-attempts failed messages: recording a deliberate skip as a
            // failure would have it fight the listener that cancelled the message, hourly.
            $log->status = $event->status;
            $log->save();

            return;
        }

        // Only promote from "pending": with a synchronous mail transport the worker has
        // already run and set the definitive status, and this must not overwrite it.
        if (SendResult::STATUS_PENDING === $log->status) {
            $log->status = $event->status;
        }

        $log->save();
    }

    /**
     * The error column carries warnings too. A message that went out without the attachment
     * someone expected is "sent" as far as the transport is concerned, and the log is the
     * only place that can explain the discrepancy.
     */
    private function describe(PostSendEvent $event): string|null
    {
        $parts = array_filter([
            $event->throwable?->getMessage(),
            ...$event->message->warnings,
        ]);

        return $parts ? implode(' ', $parts) : null;
    }
}

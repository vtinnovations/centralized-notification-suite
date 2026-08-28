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

namespace VTInnovations\CentralizedNotificationSuite;

use Contao\Model\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use VTInnovations\CentralizedNotificationSuite\Event\PostSendEvent;
use VTInnovations\CentralizedNotificationSuite\Event\PreSendEvent;
use VTInnovations\CentralizedNotificationSuite\Exception\NotificationException;
use VTInnovations\CentralizedNotificationSuite\Gateway\GatewayRegistry;
use VTInnovations\CentralizedNotificationSuite\Message\Attachment;
use VTInnovations\CentralizedNotificationSuite\Message\MessageRenderer;
use VTInnovations\CentralizedNotificationSuite\Message\PreparedMessage;
use VTInnovations\CentralizedNotificationSuite\Message\RenderedMessage;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;
use VTInnovations\CentralizedNotificationSuite\Runtime\UsageSignals;
use VTInnovations\CentralizedNotificationSuite\Token\TokenRegistry;

/**
 * Entry point for triggering a notification: resolve its published messages for the given
 * language (falling back to the message(s) flagged "fallback" if none match), render them
 * and hand each off to its gateway.
 *
 * Delivery failures never propagate. A notification is usually triggered from a front-end
 * action -- a form submission, a registration -- where the user's data has already been
 * stored by the time we get here. Letting a mail server timeout bubble up would turn that
 * into a 500 for the visitor while the side effects stay committed. Every failure is
 * therefore caught per message, recorded in the SendResult and dispatched on PostSendEvent
 * (which the send log listens to), and the remaining messages are still attempted.
 */
class CentralizedNotificationSuite
{
    public const SOURCE_API = 'api';

    public const SOURCE_FORM = 'form';

    public const SOURCE_TEST = 'test';

    public const SOURCE_RESEND = 'resend';

    public const SOURCE_CRON = 'cron';

    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly MessageRenderer $renderer,
        private readonly TokenRegistry $tokens,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
        private readonly ActivationGate $activation,
        private readonly UsageSignals $signals,
    ) {
    }

    /**
     * @param array<string, string>                                                    $tokens           Token => value pairs available to messages as ##token##
     * @param list<Attachment|array{path:string, name?:string, type?:string|null}>      $extraAttachments Added on top of each message's own configured attachments
     *
     * @throws NotificationException if no notification with that alias exists
     */
    public function send(
        string $alias,
        array $tokens,
        string|null $language = null,
        array $extraAttachments = [],
        string $source = self::SOURCE_API,
    ): SendResult {
        $result = new SendResult();

        // Dispatching is what this product does, so it is the boundary that has to be
        // licensed. Without a valid activation nothing is sent and nothing is queued, which
        // leaves the site behaving exactly as it would with the bundle absent -- a form
        // simply does not produce a notification. Content and configuration are untouched.
        $activation = $this->activation->current();

        if (!$activation->granted) {
            $this->logger->warning(\sprintf(
                'Notification "%s" was not dispatched: this installation is not activated (%s).',
                $alias,
                $activation->reason,
            ));

            return $result;
        }

        // Reporting use only when the product actually ran, and at most once per request.
        $this->signals->invoked((string) $activation->host);

        foreach ($this->prepare($alias, $tokens, $language, $extraAttachments) as $prepared) {
            if (!$prepared->isSendable()) {
                $this->logger->warning(\sprintf(
                    'Skipping message ID %d of notification "%s": %s',
                    $prepared->message->messageId,
                    $alias,
                    $prepared->problem,
                ));

                $result->add($prepared->message->messageId, SendResult::STATUS_SKIPPED, $prepared->problem);
                $this->dispatchPostSend($prepared, $source, SendResult::STATUS_SKIPPED, new NotificationException((string) $prepared->problem));

                continue;
            }

            $this->deliver($prepared, $source, $result);
        }

        return $result;
    }

    /**
     * Renders every message that would be sent, without sending anything. Used by the
     * backend preview, the test send and the notification list's status column.
     *
     * @param array<string, string>                                                $tokens
     * @param list<Attachment|array{path:string, name?:string, type?:string|null}> $extraAttachments
     *
     * @return list<PreparedMessage>
     *
     * @throws NotificationException if no notification with that alias exists
     */
    public function prepare(
        string $alias,
        array $tokens,
        string|null $language = null,
        array $extraAttachments = [],
    ): array {
        $notification = NotificationModel::findByAlias($alias);

        if (!$notification) {
            throw NotificationException::unknownAlias($alias);
        }

        $messages = MessageModel::findPublishedByPid((int) $notification->id);

        if (!$messages) {
            $this->logger->warning(\sprintf('Notification "%s" has no published messages.', $alias));

            return [];
        }

        $matching = $this->selectMessages($messages, $language);

        if (!$matching) {
            $this->logger->warning(\sprintf(
                'Notification "%s" has no message for language "%s" and none is flagged as fallback.',
                $alias,
                $language ?? '(none)',
            ));

            return [];
        }

        $attachments = array_map(
            static fn (Attachment|array $a): Attachment => $a instanceof Attachment ? $a : Attachment::fromArray($a),
            $extraAttachments,
        );

        // Add the universal tokens (##host##, ##admin_email##, ##date##, ...) so every
        // notification has them without each trigger having to remember to pass them.
        $tokens = $this->tokens->withProvidedValues($tokens, (string) $notification->type);

        $prepared = [];

        foreach ($matching as $message) {
            $rendered = $this->renderer->render($message, $alias, $tokens, $attachments);
            $gateway = GatewayModel::findByPk($message->gateway);

            $prepared[] = new PreparedMessage($rendered, $gateway, $this->findProblem($gateway));
        }

        return $prepared;
    }

    /**
     * Sends an already-rendered message through a gateway. Used for resending a logged
     * message byte-for-byte, without re-resolving tokens whose values are long gone.
     */
    public function deliver(PreparedMessage $prepared, string $source, SendResult|null $result = null): bool
    {
        $result ??= new SendResult();
        $gateway = $prepared->gateway;

        if (null === $gateway) {
            $result->add($prepared->message->messageId, SendResult::STATUS_SKIPPED, $prepared->problem);

            return false;
        }

        $config = $gateway->row();

        $preSend = $this->dispatcher->dispatch(new PreSendEvent($prepared->message, (string) $gateway->type, $config, $source));
        \assert($preSend instanceof PreSendEvent);

        if ($preSend->isCancelled()) {
            $reason = $preSend->getCancelReason() ?? 'Cancelled by a PreSendEvent listener.';
            $result->add($prepared->message->messageId, SendResult::STATUS_SKIPPED, $reason);
            $this->dispatchPostSend($prepared, $source, SendResult::STATUS_SKIPPED, new NotificationException($reason));

            return false;
        }

        $implementation = $this->gateways->get((string) $gateway->type);

        try {
            $sent = $implementation->send($prepared->message, $config);
        } catch (\Throwable $e) {
            // Deliberately broad: any transport can throw anything, and none of it may
            // reach the caller. See the class docblock.
            $this->logger->error(
                \sprintf(
                    'Failed to send message ID %d of notification "%s" via gateway "%s": %s',
                    $prepared->message->messageId,
                    $prepared->message->alias,
                    $gateway->title,
                    $e->getMessage(),
                ),
                ['exception' => $e],
            );

            $result->add($prepared->message->messageId, SendResult::STATUS_FAILED, $e->getMessage());
            $this->dispatchPostSend($prepared, $source, SendResult::STATUS_FAILED, $e);

            return false;
        }

        // An asynchronous gateway has only accepted the message, not delivered it -- the
        // send log gets the real outcome later, so do not claim more than we know here.
        $accepted = $implementation->isAsynchronous() ? SendResult::STATUS_QUEUED : SendResult::STATUS_SENT;

        $status = $sent ? $accepted : SendResult::STATUS_FAILED;

        $result->add($prepared->message->messageId, $status);
        $this->dispatchPostSend($prepared, $source, $status, $sent ? null : new NotificationException('The gateway reported the message as not sent.'));

        return $sent;
    }

    private function dispatchPostSend(PreparedMessage $prepared, string $source, string $status, \Throwable|null $throwable): void
    {
        $this->dispatcher->dispatch(new PostSendEvent(
            $prepared->message,
            $prepared->getGatewayType(),
            $prepared->gateway?->row() ?? [],
            $source,
            $status,
            $throwable,
        ));
    }

    /**
     * Explains why a message's gateway cannot deliver, or null if it can.
     */
    private function findProblem(GatewayModel|null $gateway): string|null
    {
        if (!$gateway) {
            return 'its gateway no longer exists';
        }

        if (!$gateway->published) {
            return \sprintf('its gateway "%s" is not published', $gateway->title);
        }

        if (!$this->gateways->has((string) $gateway->type)) {
            return \sprintf('no gateway is registered for type "%s"', $gateway->type);
        }

        return null;
    }

    /**
     * @return array<MessageModel>
     */
    private function selectMessages(Collection $messages, string|null $language): array
    {
        $exact = [];
        $fallback = [];

        foreach ($messages as $message) {
            if (null !== $language && $message->language === $language) {
                $exact[] = $message;
            } elseif ($message->fallback) {
                $fallback[] = $message;
            }
        }

        return $exact ?: $fallback;
    }

    /**
     * Convenience wrapper for callers that only need to know whether a rendered message
     * went out, bypassing notification lookup entirely.
     */
    public function deliverRendered(RenderedMessage $message, GatewayModel $gateway, string $source): bool
    {
        return $this->deliver(new PreparedMessage($message, $gateway, $this->findProblem($gateway)), $source);
    }
}

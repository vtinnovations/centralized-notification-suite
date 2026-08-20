<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener;

use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use VTInnovations\SimpleNotifyBundle\Exception\SimpleNotifyException;
use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;
use VTInnovations\SimpleNotifyBundle\SimpleNotifyCenter;

/**
 * Shared plumbing for the hook listeners: read the notifications configured on a module,
 * send them, and never let a delivery problem reach the visitor.
 *
 * Every trigger here runs after something has already been committed -- an account created,
 * a comment stored, a subscription confirmed. Throwing at that point would show an error
 * for an action that in fact succeeded, so failures are logged and swallowed.
 */
class NotificationTrigger
{
    public function __construct(
        private readonly SimpleNotifyCenter $notifyCenter,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param mixed                 $source A module or content element carrying simple_notify_notifications
     * @param array<string, string> $tokens
     */
    public function trigger(mixed $source, array $tokens, string $context): void
    {
        $ids = StringUtil::deserialize($this->readSetting($source), true);

        if (!$ids) {
            return;
        }

        $language = $this->requestStack->getCurrentRequest()?->getLocale();

        foreach (NotificationModel::findMultipleByIds($ids) ?? [] as $notification) {
            try {
                $this->notifyCenter->send(
                    (string) $notification->alias,
                    $tokens,
                    $language,
                    [],
                    SimpleNotifyCenter::SOURCE_API,
                );
            } catch (SimpleNotifyException $e) {
                $this->logger->error(
                    \sprintf('Simple Notify (%s): %s', $context, $e->getMessage()),
                    ['exception' => $e],
                );
            }
        }
    }

    /**
     * Sends every published notification of a given type.
     *
     * Used where the trigger cannot tell us which notification to send. A comment, for
     * instance, is stored against a news item or a page -- the content element that rendered
     * the comment form is not recoverable from it, so there is nothing to hang a per-module
     * setting on. Creating a "comment" notification is itself the opt-in.
     *
     * @param array<string, string> $tokens
     */
    public function triggerByType(string $type, array $tokens, string $context): void
    {
        $notifications = NotificationModel::findByType($type);

        if (!$notifications) {
            return;
        }

        $language = $this->requestStack->getCurrentRequest()?->getLocale();

        foreach ($notifications as $notification) {
            try {
                $this->notifyCenter->send(
                    (string) $notification->alias,
                    $tokens,
                    $language,
                    [],
                    SimpleNotifyCenter::SOURCE_API,
                );
            } catch (SimpleNotifyException $e) {
                $this->logger->error(
                    \sprintf('Simple Notify (%s): %s', $context, $e->getMessage()),
                    ['exception' => $e],
                );
            }
        }
    }

    /**
     * Modules and content elements expose their DCA row as magic properties, but the
     * concrete classes differ, so this stays deliberately untyped.
     */
    private function readSetting(mixed $source): string|null
    {
        if (\is_array($source)) {
            return $source[AddNotificationFieldListener::FIELD] ?? null;
        }

        if (!\is_object($source)) {
            return null;
        }

        $value = $source->{AddNotificationFieldListener::FIELD} ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * Flattens a model or submitted-data array into ##tokens##, prefixed to keep member
     * fields from colliding with the universal ones.
     *
     * @param iterable<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function prefixTokens(iterable $data, string $prefix): array
    {
        $tokens = [];

        foreach ($data as $key => $value) {
            if (!\is_string($key) || \in_array($key, self::SENSITIVE, true)) {
                continue;
            }

            if (\is_array($value)) {
                $value = implode(', ', array_filter($value, static fn ($v): bool => is_scalar($v)));
            }

            if (!is_scalar($value) && null !== $value) {
                continue;
            }

            $tokens[$prefix.$key] = (string) $value;
        }

        return $tokens;
    }

    /**
     * Never exposed as tokens. A password hash or a session token in a notification body
     * would be a data leak that is very easy to create by accident and hard to notice.
     */
    private const SENSITIVE = [
        'password',
        'newPassword',
        'confirmPassword',
        'activation',
        'session',
        'secret',
        'backupCodes',
        'trustedTokenVersion',
        'useTwoFactor',
    ];
}

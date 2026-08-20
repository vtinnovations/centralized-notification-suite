<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\NewsletterChannelModel;

/**
 * Sends a notification when someone confirms or cancels a newsletter subscription --
 * typically a welcome mail, or an internal heads-up that a subscriber left.
 *
 * Which notifications go out is configured on the subscribe/unsubscribe module. Both hooks
 * only exist when the newsletter bundle is installed.
 */
class NewsletterNotificationListener
{
    public function __construct(private readonly NotificationTrigger $trigger)
    {
    }

    /**
     * @param list<int|string> $channelIds Channels the recipient was added to
     * @param list<string>     $cids       Confirmation IDs, not useful in a message
     */
    #[AsHook('activateRecipient')]
    public function onActivate(string $email, array $channelIds, array $cids = [], mixed $module = null): void
    {
        $this->trigger->trigger(
            $module,
            $this->tokens($email, $channelIds, 'subscribed'),
            'newsletter subscription',
        );
    }

    /**
     * @param list<int|string> $channelIds Channels the recipient was removed from
     */
    #[AsHook('removeRecipient')]
    public function onRemove(string $email, array $channelIds, mixed $module = null): void
    {
        $this->trigger->trigger(
            $module,
            $this->tokens($email, $channelIds, 'unsubscribed'),
            'newsletter cancellation',
        );
    }

    /**
     * @param list<int|string> $channelIds
     *
     * @return array<string, string>
     */
    private function tokens(string $email, array $channelIds, string $event): array
    {
        $ids = array_filter(array_map('intval', $channelIds));

        return [
            'recipient_email' => $email,
            'newsletter_event' => $event,
            'newsletter_channel_ids' => implode(', ', $ids),
            // Titles rather than IDs: a welcome mail wants to name the newsletter
            'newsletter_channels' => implode(', ', $this->channelTitles($ids)),
        ];
    }

    /**
     * @param list<int> $ids
     *
     * @return list<string>
     */
    private function channelTitles(array $ids): array
    {
        if (!$ids || !class_exists(NewsletterChannelModel::class)) {
            return [];
        }

        $titles = [];

        foreach (NewsletterChannelModel::findMultipleByIds($ids) ?? [] as $channel) {
            $titles[] = (string) $channel->title;
        }

        return $titles;
    }
}

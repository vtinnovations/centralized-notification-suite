<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;

/**
 * Sends a notification when a comment is posted. The case that matters is moderation: a
 * comment awaiting approval is invisible until someone happens to look at the backend.
 *
 * Unlike forms and member modules, this triggers every published notification of type
 * "comment" rather than reading a per-element setting. A comment is stored against the
 * commented record (a news item, a page), and the content element that rendered the form is
 * not recoverable from it -- there is nothing to attach a per-element setting to. Creating a
 * comment notification is therefore the opt-in, and ##comment_source## can be used in an
 * {if} block to branch on where the comment came from.
 *
 * The hook only fires when the comments bundle is installed.
 */
class CommentNotificationListener
{
    public function __construct(private readonly NotificationTrigger $trigger)
    {
    }

    /**
     * @param array<string, mixed> $data The stored tl_comments row
     */
    #[AsHook('addComment')]
    public function __invoke(int $commentId, array $data, mixed $comments = null): void
    {
        $awaiting = empty($data['published']);

        $tokens = [
            ...$this->trigger->prefixTokens($data, 'comment_'),
            'comment_id' => (string) $commentId,
            // The single most useful fact in a moderation mail
            'comment_published' => $awaiting ? '' : '1',
            'comment_awaiting_moderation' => $awaiting ? '1' : '',
        ];

        $this->trigger->triggerByType(NotificationModel::TYPE_COMMENT, $tokens, 'new comment');
    }
}

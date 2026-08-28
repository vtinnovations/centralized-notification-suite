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

namespace VTInnovations\CentralizedNotificationSuite\Token;

/**
 * Documents the tokens the member, comment and newsletter triggers provide.
 *
 * One class with three configured instances rather than three near-identical classes: the
 * only thing that differs is the type and the list, and the values all come from the hook
 * (which is why getValues() is empty). The lists are constants so services.yaml can
 * reference them directly with !php/const.
 */
class TriggerTokenProvider implements TokenProviderInterface
{
    public const MEMBER = [
        'member_id' => 'ID of the member',
        'member_firstname' => 'First name',
        'member_lastname' => 'Last name',
        'member_email' => 'Email address',
        'member_username' => 'Username, if the account can log in',
        'member_<field>' => 'Any other member field, including your own custom ones',
        'member_event' => 'What happened: registration, activation, profile_updated, password_changed or account_closed',
        'member_close_mode' => 'How the account was closed, for account_closed only',
    ];

    public const COMMENT = [
        'comment_id' => 'ID of the comment',
        'comment_name' => 'Name the commenter gave',
        'comment_email' => 'Their email address',
        'comment_website' => 'Their website, if given',
        'comment_comment' => 'The comment text',
        'comment_source' => 'Table the comment belongs to, e.g. tl_news',
        'comment_parent' => 'ID of the commented record',
        'comment_published' => '1 when the comment is already visible',
        'comment_awaiting_moderation' => '1 when the comment still needs approval',
    ];

    public const NEWSLETTER = [
        'recipient_email' => 'Address that subscribed or unsubscribed',
        'newsletter_event' => 'Either subscribed or unsubscribed',
        'newsletter_channels' => 'Titles of the affected newsletters',
        'newsletter_channel_ids' => 'IDs of the affected newsletters',
    ];

    /**
     * @param array<string, string> $definitions
     */
    public function __construct(
        private readonly string $type,
        private readonly array $definitions,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function getValues(): array
    {
        return [];
    }



}

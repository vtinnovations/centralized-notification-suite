<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\MemberModel;

/**
 * Sends notifications for member actions: registration, activation, profile changes,
 * password changes and account closure.
 *
 * Which notifications go out is configured on the front-end module itself (see
 * AddNotificationFieldListener), so different registration forms can send different mail.
 *
 * Passwords and activation tokens are never exposed as tokens -- see
 * NotificationTrigger::SENSITIVE.
 */
class MemberNotificationListener
{
    public function __construct(private readonly NotificationTrigger $trigger)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    #[AsHook('createNewUser')]
    public function onCreateNewUser(int $userId, array $data, mixed $module = null): void
    {
        $tokens = [
            ...$this->trigger->prefixTokens($data, 'member_'),
            'member_id' => (string) $userId,
            'member_event' => 'registration',
        ];

        $this->trigger->trigger($module, $tokens, 'member registration');
    }

    #[AsHook('activateAccount')]
    public function onActivateAccount(MemberModel $member, mixed $module = null): void
    {
        $this->trigger->trigger(
            $module,
            [...$this->memberTokens($member), 'member_event' => 'activation'],
            'member activation',
        );
    }

    /**
     * @param array<string, mixed> $submitted
     * @param array<string, mixed> $files
     */
    #[AsHook('updatePersonalData')]
    public function onUpdatePersonalData(mixed $user, array $submitted, mixed $module = null, array $files = []): void
    {
        $tokens = [
            ...$this->trigger->prefixTokens($submitted, 'member_'),
            'member_id' => (string) ($user->id ?? ''),
            'member_event' => 'profile_updated',
        ];

        $this->trigger->trigger($module, $tokens, 'personal data update');
    }

    /**
     * The password itself is deliberately not passed on.
     */
    #[AsHook('setNewPassword')]
    public function onSetNewPassword(MemberModel $member, string $password, mixed $module = null): void
    {
        $this->trigger->trigger(
            $module,
            [...$this->memberTokens($member), 'member_event' => 'password_changed'],
            'password change',
        );
    }

    #[AsHook('closeAccount')]
    public function onCloseAccount(int $userId, string $mode, mixed $module = null): void
    {
        $member = MemberModel::findByPk($userId);

        $tokens = [
            ...(null !== $member ? $this->memberTokens($member) : ['member_id' => (string) $userId]),
            'member_event' => 'account_closed',
            'member_close_mode' => $mode,
        ];

        $this->trigger->trigger($module, $tokens, 'account closure');
    }

    /**
     * @return array<string, string>
     */
    private function memberTokens(MemberModel $member): array
    {
        return [
            ...$this->trigger->prefixTokens($member->row(), 'member_'),
            'member_id' => (string) $member->id,
        ];
    }
}

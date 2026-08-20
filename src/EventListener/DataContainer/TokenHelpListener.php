<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use VTInnovations\SimpleNotifyBundle\Model\MessageModel;
use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;
use VTInnovations\SimpleNotifyBundle\Token\TokenRegistry;

/**
 * Fills the help wizard on the message body fields with the tokens actually available to
 * this notification, taken from its type.
 *
 * Contao renders TL_LANG['XPL'][<key>] as a two-column table when it is an array, so the
 * reference is built here rather than written into a language file: the list depends on the
 * record being edited and on which token providers are installed.
 */
class TokenHelpListener
{
    private const XPL_KEY = 'simple_notify_tokens';

    public function __construct(
        private readonly TokenRegistry $registry,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback(table: 'tl_simple_message', target: 'config.onload')]
    public function __invoke(DataContainer|null $dc = null): void
    {
        $type = $this->resolveNotificationType($dc);

        System::loadLanguageFile('explain');

        $rows = [[
            $GLOBALS['TL_LANG']['tl_simple_message']['tokenHelpHeader'][0] ?? 'Token',
            $GLOBALS['TL_LANG']['tl_simple_message']['tokenHelpHeader'][1] ?? 'Contains',
        ]];

        foreach ($this->registry->getDefinitionsFor($type) as $token => $description) {
            // Angle brackets mark a family of tokens whose real names come from the data
            // (##<field name>##); they still show the shape an editor has to type.
            $rows[] = ['##'.$token.'##', $description];
        }

        $GLOBALS['TL_LANG']['XPL'][self::XPL_KEY] = $rows;

        foreach (['text', 'html', 'subject'] as $field) {
            $GLOBALS['TL_DCA']['tl_simple_message']['fields'][$field]['explanation'] = self::XPL_KEY;
        }
    }

    /**
     * The message list is always opened from its notification, so the type comes from the
     * parent record -- either the current one or, when editing a single message, its pid.
     */
    private function resolveNotificationType(DataContainer|null $dc): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $pid = (int) ($request?->query->get('id') ?? 0);

        // act=edit means the id is the message, not the notification
        if ($request && \in_array($request->query->get('act'), ['edit', 'show'], true)) {
            $pid = (int) (MessageModel::findByPk($pid)?->pid ?? 0);
        }

        if (0 === $pid && null !== $dc) {
            $pid = (int) ($dc->activeRecord->pid ?? 0);
        }

        return (string) (NotificationModel::findByPk($pid)?->type ?: NotificationModel::TYPE_CUSTOM);
    }
}

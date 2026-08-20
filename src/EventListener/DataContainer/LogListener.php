<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\Message;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use VTInnovations\SimpleNotifyBundle\Cron\PruneLogCron;
use VTInnovations\SimpleNotifyBundle\Message\LogReplayer;
use VTInnovations\SimpleNotifyBundle\Model\LogModel;
use VTInnovations\SimpleNotifyBundle\SendResult;
use VTInnovations\SimpleNotifyBundle\SimpleNotifyCenter;

/**
 * Backend behaviour for the send log: the resend and prune actions, and the coloured
 * status column that makes a failed delivery visible from the list rather than only after
 * opening a record.
 */
class LogListener
{
    private const ACTIONS = ['resend', 'clear'];

    public function __construct(
        private readonly SimpleNotifyCenter $notifyCenter,
        private readonly LogReplayer $replayer,
        private readonly PruneLogCron $pruneCron,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly RequestStack $requestStack,
        private readonly string $csrfTokenName,
    ) {
    }

    #[AsCallback(table: 'tl_simple_log', target: 'config.onload')]
    public function handleActions(DataContainer|null $dc = null): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $key = (string) $request->query->get('key');

        if (!\in_array($key, self::ACTIONS, true)) {
            return;
        }

        // These change state from a GET link, so the request token has to be validated
        // explicitly -- Contao only does that automatically for POST requests.
        if (!$this->tokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, (string) $request->query->get('rt')))) {
            throw new AccessDeniedException('Invalid request token for a Simple Notify log action.');
        }

        if ('clear' === $key) {
            $this->clear();
        } else {
            $this->resend((int) $request->query->get('id'));
        }

        Controller::redirect(Backend::addToUrl('', true, ['key', 'id', 'rt']));
    }

    private function resend(int $id): void
    {
        $entry = LogModel::findByPk($id);

        if (!$entry) {
            throw new AccessDeniedException(\sprintf('Send log entry ID %d does not exist.', $id));
        }

        $prepared = $this->replayer->toPrepared($entry);

        if (!$prepared->isSendable()) {
            Message::addError($this->trans('resendImpossible', [(string) $prepared->problem]));

            return;
        }

        if ($this->notifyCenter->deliver($prepared, SimpleNotifyCenter::SOURCE_RESEND)) {
            Message::addConfirmation($this->trans('resendOk', [$prepared->message->recipients]));

            return;
        }

        // deliver() never throws; SendLogListener has written the reason back to the entry
        Message::addError($this->trans('resendFailed', [(string) LogModel::findByPk($id)?->error]));
    }

    /**
     * Empties the log. Retention is the cron job's business; this is the manual "I have
     * read these, get them out of my way" action, so it clears everything.
     */
    private function clear(): void
    {
        Message::addConfirmation($this->trans('clearOk', [$this->pruneCron->prune(0)]));
    }

    /**
     * Renders the resend link, or an inert icon when the entry cannot be replayed at all
     * -- better than a button whose only possible outcome is an error message.
     *
     * @param array<string, mixed> $row
     */
    #[AsCallback(table: 'tl_simple_log', target: 'list.operations.resend.button')]
    public function resendButton(array $row, string|null $href, string $label, string $title, string|null $icon, string $attributes): string
    {
        $entry = LogModel::findByPk($row['id']);

        if (!$entry || !$this->replayer->isReplayable($entry)) {
            return \sprintf(
                '<span style="opacity:.3" title="%s">%s</span> ',
                StringUtil::specialchars($this->trans('resendUnavailable')),
                Image::getHtml((string) $icon, $label),
            );
        }

        return \sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl('key=resend&amp;id='.$row['id'].'&amp;rt='.$this->tokenManager->getDefaultTokenValue()),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml((string) $icon, $label),
        );
    }

    /**
     * The global "clear" link needs the request token appended, which a static DCA href
     * cannot carry.
     */
    #[AsCallback(table: 'tl_simple_log', target: 'list.global_operations.clear.button')]
    public function clearButton(string|null $href, string $label, string $title, string $class, string $attributes): string
    {
        return \sprintf(
            '<a href="%s" class="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href.'&amp;rt='.$this->tokenManager->getDefaultTokenValue()),
            $class,
            StringUtil::specialchars($title),
            $attributes,
            $label,
        );
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string>   $args
     *
     * @return array<int, string>
     */
    #[AsCallback(table: 'tl_simple_log', target: 'list.label.label')]
    public function formatLabel(array $row, string $label, DataContainer $dc, array $args): array
    {
        $colours = [
            SendResult::STATUS_SENT => '#4caf50',
            SendResult::STATUS_QUEUED => '#1e88e5',
            SendResult::STATUS_PENDING => '#757575',
            SendResult::STATUS_FAILED => '#e53935',
            SendResult::STATUS_SKIPPED => '#fb8c00',
        ];

        $status = (string) $row['status'];
        $text = $GLOBALS['TL_LANG']['tl_simple_log']['status_options'][$status] ?? $status;

        $args[0] = \sprintf(
            '<span style="display:inline-block;padding:1px 8px;border-radius:9px;color:#fff;font-size:.85em;white-space:nowrap;background:%s">%s</span>',
            $colours[$status] ?? '#757575',
            StringUtil::specialchars((string) $text),
        );

        // A non-successful row is only actionable with the reason attached
        if (SendResult::STATUS_SENT !== $status && '' !== (string) ($row['error'] ?? '')) {
            $args[0] .= \sprintf(
                ' <span style="color:#999" title="%s">%s</span>',
                StringUtil::specialchars((string) $row['error']),
                StringUtil::specialchars(StringUtil::substr((string) $row['error'], 50)),
            );
        }

        $args[1] = Date::parse((string) Config::get('datimFormat'), (int) $row['tstamp']);

        return $args;
    }

    /**
     * @param array<int, string|int> $params
     */
    private function trans(string $key, array $params = []): string
    {
        $pattern = $GLOBALS['TL_LANG']['tl_simple_log'][$key] ?? $key;

        return $params ? \vsprintf((string) $pattern, $params) : (string) $pattern;
    }
}

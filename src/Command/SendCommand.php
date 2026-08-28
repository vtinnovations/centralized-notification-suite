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

namespace VTInnovations\CentralizedNotificationSuite\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VTInnovations\CentralizedNotificationSuite\Exception\NotificationException;
use VTInnovations\CentralizedNotificationSuite\SendResult;
use VTInnovations\CentralizedNotificationSuite\CentralizedNotificationSuite;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;

/**
 * Triggers a notification from the command line.
 *
 * Useful for scripted and cron-driven notifications, and for checking a notification on a
 * server without a browser session -- including which messages a given language resolves to.
 */
#[AsCommand(
    name: 'notification:send',
    description: 'Trigger a notification by alias',
)]
class SendCommand extends Command
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly CentralizedNotificationSuite $notifyCenter,
        private readonly ActivationGate $activation,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('alias', InputArgument::REQUIRED, 'The notification alias')
            ->addOption('token', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A token as name=value (repeatable)')
            ->addOption('tokens-json', null, InputOption::VALUE_REQUIRED, 'All tokens as a JSON object')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Pick the message for this language instead of the fallback')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Render and report what would be sent, without sending')
            ->setHelp(<<<'HELP'
                Trigger a notification:

                  <info>%command.full_name% welcome-mail -t name=Jane -t email=jane@example.com</info>

                Or pass the tokens as JSON, which handles values containing "=" or newlines:

                  <info>%command.full_name% welcome-mail --tokens-json='{"name":"Jane"}'</info>

                Use <info>--dry-run</info> to see the resolved recipients and subject without sending.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        $alias = (string) $input->getArgument('alias');

        try {
            $tokens = $this->parseTokens($input);
        } catch (\JsonException $e) {
            $io->error('--tokens-json is not valid JSON: '.$e->getMessage());

            return Command::INVALID;
        }

        $language = $input->getOption('language');
        $language = \is_string($language) && '' !== $language ? $language : null;

        try {
            if ($input->getOption('dry-run')) {
                return $this->dryRun($io, $alias, $tokens, $language);
            }

            $result = $this->notifyCenter->send($alias, $tokens, $language, [], CentralizedNotificationSuite::SOURCE_CRON);
        } catch (NotificationException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        return $this->report($io, $result);
    }

    /**
     * @param array<string, string> $tokens
     */
    private function dryRun(SymfonyStyle $io, string $alias, array $tokens, string|null $language): int
    {
        $prepared = $this->notifyCenter->prepare($alias, $tokens, $language);

        if (!$prepared) {
            $io->warning(\sprintf('Notification "%s" resolved to no messages.', $alias));

            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($prepared as $item) {
            $rows[] = [
                $item->message->messageId,
                $item->isSendable() ? 'would send' : 'blocked',
                $item->getGatewayType() ?: '-',
                implode(', ', $item->message->getRecipientList()) ?: '(none)',
                $item->message->subject,
                $item->problem ?? '',
            ];
        }

        $io->table(['Message', 'Status', 'Gateway', 'Recipients', 'Subject', 'Problem'], $rows);
        $io->note('Dry run: nothing was sent.');

        return Command::SUCCESS;
    }

    private function report(SymfonyStyle $io, SendResult $result): int
    {
        $statuses = $result->getStatuses();

        if (!$statuses) {
            // An unlicensed installation produces the same empty result as a misconfigured
            // notification, and telling an operator to check their fallback message when the
            // real problem is activation would send them looking in the wrong place.
            if (!$this->activation->current()->granted) {
                $io->warning('Nothing was sent: this installation is not activated. Enter a licence key under Contao > Settings.');

                return Command::SUCCESS;
            }

            $io->warning('The notification resolved to no messages. Check that it has a published message for this language, or one flagged as fallback.');

            return Command::SUCCESS;
        }

        $errors = $result->getErrors();
        $rows = [];

        foreach ($statuses as $messageId => $status) {
            $rows[] = [$messageId, $status, $errors[$messageId] ?? ''];
        }

        $io->table(['Message', 'Status', 'Error'], $rows);

        if ($result->hasFailures()) {
            $io->error('Some messages failed. The send log has the details.');

            return Command::FAILURE;
        }

        // "queued" is the honest answer for e-mail: Contao hands it to a Messenger worker
        $io->success(\sprintf('%d message(s) accepted by their gateway.', $result->countSent()));

        return Command::SUCCESS;
    }

    /**
     * @return array<string, string>
     *
     * @throws \JsonException
     */
    private function parseTokens(InputInterface $input): array
    {
        $tokens = [];

        $json = $input->getOption('tokens-json');

        if (\is_string($json) && '' !== $json) {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            foreach (\is_array($decoded) ? $decoded : [] as $name => $value) {
                $tokens[(string) $name] = \is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        foreach ((array) $input->getOption('token') as $pair) {
            // Split on the first "=" only, so values may contain it
            [$name, $value] = array_pad(explode('=', (string) $pair, 2), 2, '');
            $name = trim($name);

            if ('' !== $name) {
                $tokens[$name] = $value;
            }
        }

        return $tokens;
    }
}

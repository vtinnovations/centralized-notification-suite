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
use Contao\CoreBundle\Slug\Slug;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Token\NcTokenAliasResolver;

/**
 * Imports notifications, messages and gateways from terminal42/notification_center.
 *
 * Deliberately a command and not a Migration: rewriting a site's notification configuration
 * is a decision, not something that should happen as a side effect of contao:migrate.
 *
 * Notification Center's schema is notification -> message -> language, one level deeper than
 * ours, because its message level exists mainly to carry a gateway. Each NC *language* row
 * therefore becomes one of our messages, with the gateway taken from its parent NC message.
 *
 * Columns are read defensively rather than assumed: NC's schema differs between versions and
 * across gateway extensions, and it is better to import what is there and report the rest
 * than to fail on a column that a given install happens not to have.
 */
#[AsCommand(
    name: 'notification:import-nc',
    description: 'Import notifications, messages and gateways from Notification Center',
)]
class ImportFromNotificationCenterCommand extends Command
{
    private const TABLES = ['tl_nc_notification', 'tl_nc_message', 'tl_nc_language', 'tl_nc_gateway'];

    /**
     * NC notification types are namespaced strings like "core_form" or
     * "core_member_registration"; matched on substrings so extension types land sensibly.
     */
    private const TYPE_HINTS = [
        'form' => NotificationModel::TYPE_FORM,
        'member' => NotificationModel::TYPE_MEMBER,
        'registration' => NotificationModel::TYPE_MEMBER,
        'password' => NotificationModel::TYPE_MEMBER,
        'personaldata' => NotificationModel::TYPE_MEMBER,
        'comment' => NotificationModel::TYPE_COMMENT,
        'newsletter' => NotificationModel::TYPE_NEWSLETTER,
        'subscri' => NotificationModel::TYPE_NEWSLETTER,
    ];

    /** @var array<string, int> NC gateway id => our gateway id */
    private array $gatewayMap = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly Slug $slug,
        private readonly NcTokenAliasResolver $tokenResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would be imported without writing anything')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Import again even if notifications with the same alias already exist')
            ->setHelp(<<<'HELP'
                Reads the tl_nc_* tables of terminal42/notification_center and recreates its
                configuration in this bundle. Notification Center itself is left untouched, so
                both can coexist until you are satisfied with the result.

                Always start with a dry run:

                  <info>%command.full_name% --dry-run</info>

                Token names are translated as they are imported (##form_email## becomes
                ##email##, ##raw_data## becomes ##all_fields##, ...). Anything the importer does
                not recognise is left as-is and listed at the end for you to check.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();
        $this->tokenResolver->reset();

        if (!$missing = $this->checkTables()) {
            $dryRun = (bool) $input->getOption('dry-run');

            $io->title('Importing from Notification Center'.($dryRun ? ' (dry run)' : ''));

            $this->importGateways($io, $dryRun);
            $this->importNotifications($io, $dryRun, (bool) $input->getOption('force'));
            $this->reportTokens($io);

            if ($dryRun) {
                $io->note('Dry run: nothing was written.');
            } else {
                $io->success('Import finished. Review the results under Notify > Notifications before switching over.');
            }

            return Command::SUCCESS;
        }

        $io->error(\sprintf(
            'Notification Center tables not found (%s). Nothing to import.',
            implode(', ', $missing),
        ));

        return Command::FAILURE;
    }

    /**
     * @return list<string> Missing tables, empty when everything is present
     */
    private function checkTables(): array
    {
        $schema = $this->connection->createSchemaManager();
        $existing = $schema->listTableNames();

        return array_values(array_diff(self::TABLES, $existing));
    }

    private function importGateways(SymfonyStyle $io, bool $dryRun): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM tl_nc_gateway ORDER BY id');
        $reported = [];
        $unsupported = [];

        foreach ($rows as $row) {
            $ncType = (string) ($row['type'] ?? '');

            // Only e-mail maps cleanly. An NC install using an SMS or push gateway needs a
            // matching gateway here first, so those are reported rather than half-imported.
            if ('email' !== $ncType) {
                $unsupported[] = \sprintf('%s (type "%s")', $row['title'] ?? '?', $ncType);

                continue;
            }

            $title = (string) ($row['title'] ?? 'Imported gateway');
            $existing = GatewayModel::findOneBy('title', $title);

            if (!$dryRun) {
                $gateway = $existing ?? new GatewayModel();
                $gateway->tstamp = time();
                $gateway->title = $title;
                $gateway->type = 'email';
                $gateway->sender_name = (string) $this->pick($row, ['email_sender_name', 'sender_name']);
                $gateway->sender_email = (string) $this->pick($row, ['email_sender_address', 'sender_email', 'email_sender']);
                $gateway->reply_to = (string) $this->pick($row, ['email_replyTo', 'email_reply_to']);
                $gateway->mailer_transport = (string) $this->pick($row, ['mailerTransport', 'mailer_transport']);
                $gateway->published = '1';
                $gateway->save();

                $this->gatewayMap[(string) $row['id']] = (int) $gateway->id;
            }

            $reported[] = [$row['id'], $title, $existing ? 'updated' : 'created'];
        }

        if ($reported) {
            $io->section('Senders');
            $io->table(['NC id', 'Title', 'Action'], $reported);
        }

        if ($unsupported) {
            $io->warning(
                "These gateways were skipped because no matching gateway type is installed here:\n - "
                .implode("\n - ", $unsupported)
                ."\nInstall or write a gateway for them, then re-run the import.",
            );
        }
    }

    private function importNotifications(SymfonyStyle $io, bool $dryRun, bool $force): void
    {
        $notifications = $this->connection->fetchAllAssociative('SELECT * FROM tl_nc_notification ORDER BY id');
        $rows = [];

        foreach ($notifications as $nc) {
            $title = (string) ($nc['title'] ?? 'Imported notification');

            // The unsuffixed alias is what a previous run of this import would have used,
            // so it is also how an already-imported notification is recognised. Only a
            // genuinely new notification gets a "-2" style suffix on collision.
            $baseAlias = $this->slug->generate($title);
            $existing = NotificationModel::findOneBy('alias', $baseAlias);

            if ($existing && !$force) {
                $rows[] = [$nc['id'], $title, $baseAlias, '-', 'skipped (already imported)'];

                continue;
            }

            $alias = $existing ? $baseAlias : $this->uniqueAlias($baseAlias);
            $languageRows = $this->findLanguageRows((int) $nc['id']);

            $notificationId = 0;

            if (!$dryRun) {
                $notification = $existing ?? new NotificationModel();
                $notification->tstamp = time();
                $notification->title = $title;
                $notification->alias = $alias;
                $notification->type = $this->mapType((string) ($nc['type'] ?? ''));
                $notification->save();

                $notificationId = (int) $notification->id;

                // Replace the messages rather than adding to them, so --force is idempotent
                // instead of doubling the message list on every run.
                if ($existing) {
                    $this->connection->executeStatement(
                        'DELETE FROM tl_notification_message WHERE pid = :pid',
                        ['pid' => $notificationId],
                    );
                }
            }

            // Built even on a dry run: rewriting the bodies is what discovers unmappable
            // tokens, and knowing about those before committing is the point of a dry run.
            foreach ($languageRows as $language) {
                $this->importMessage($notificationId, $language, $dryRun);
            }

            $rows[] = [
                $nc['id'],
                $title,
                $alias,
                \count($languageRows),
                $existing ? 'replaced' : 'created',
            ];
        }

        $io->section('Notifications');
        $io->table(['NC id', 'Title', 'Alias', 'Messages', 'Action'], $rows);
    }

    /**
     * NC's language rows joined to their parent message, which is where the gateway lives.
     *
     * @return list<array<string, mixed>>
     */
    private function findLanguageRows(int $notificationId): array
    {
        return $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT l.*, m.gateway AS nc_gateway, m.gateway_type AS nc_gateway_type,
                       m.published AS nc_published, m.title AS nc_message_title
                FROM tl_nc_language l
                INNER JOIN tl_nc_message m ON m.id = l.pid
                WHERE m.pid = :pid
                ORDER BY m.id, l.id
                SQL,
            ['pid' => $notificationId],
        );
    }

    /**
     * @param array<string, mixed> $nc
     */
    private function importMessage(int $notificationId, array $nc, bool $dryRun = false): void
    {
        $message = new MessageModel();
        $message->tstamp = time();
        $message->pid = $notificationId;
        $message->gateway = $this->gatewayMap[(string) ($nc['nc_gateway'] ?? '')] ?? 0;
        $message->language = (string) ($nc['language'] ?? '');
        $message->fallback = !empty($nc['fallback']) ? '1' : '';
        $message->subject = $this->tokenResolver->rewrite((string) $this->pick($nc, ['email_subject', 'subject']));
        $message->text = $this->tokenResolver->rewrite((string) $this->pick($nc, ['email_text', 'text']));

        $html = (string) $this->pick($nc, ['email_html', 'html']);
        $message->html = '' !== trim($html) ? $this->tokenResolver->rewrite($html) : null;

        $message->recipients = $this->tokenResolver->rewrite((string) $this->pick($nc, ['email_recipient', 'recipients', 'recipient']));
        $message->cc = $this->tokenResolver->rewrite((string) $this->pick($nc, ['email_recipient_cc', 'cc']));
        $message->bcc = $this->tokenResolver->rewrite((string) $this->pick($nc, ['email_recipient_bcc', 'bcc']));
        $message->reply_to = $this->tokenResolver->rewrite((string) $this->pick($nc, ['email_replyTo', 'reply_to']));
        $message->priority = (int) ($this->pick($nc, ['email_priority', 'priority']) ?: 3);
        $message->attachments = $nc['attachments'] ?? null;

        // NC's "email_mode" of "htmlOnly" means there is no hand-written text part, so let
        // ours be generated from the HTML rather than shipping an empty alternative.
        $message->auto_plaintext = 'htmlOnly' === ($nc['email_mode'] ?? '') || '' === trim((string) $message->text) ? '1' : '';
        $message->template = 0;
        $message->embed_images = '';
        $message->published = !empty($nc['nc_published']) ? '1' : '';
        $message->start = '';
        $message->stop = '';

        if (!$dryRun) {
            $message->save();
        }
    }

    /**
     * NC schemas vary between versions and gateway extensions, so take the first column
     * that actually exists and holds something.
     *
     * @param array<string, mixed> $row
     * @param list<string>         $candidates
     */
    private function pick(array $row, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            if (isset($row[$candidate]) && '' !== $row[$candidate]) {
                return $row[$candidate];
            }
        }

        return '';
    }

    private function mapType(string $ncType): string
    {
        $needle = strtolower($ncType);

        foreach (self::TYPE_HINTS as $hint => $type) {
            if (str_contains($needle, $hint)) {
                return $type;
            }
        }

        return NotificationModel::TYPE_CUSTOM;
    }

    /**
     * Appends a numeric suffix until the alias is free. Used only for notifications this
     * import is creating for the first time.
     */
    private function uniqueAlias(string $base): string
    {
        $alias = $base;

        for ($i = 2; $this->aliasExists($alias); ++$i) {
            $alias = $base.'-'.$i;
        }

        return $alias;
    }

    private function aliasExists(string $alias): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT id FROM tl_notification WHERE alias = :alias',
            ['alias' => $alias],
        );
    }

    private function reportTokens(SymfonyStyle $io): void
    {
        $unmapped = $this->tokenResolver->getUnmapped();

        if (!$unmapped) {
            return;
        }

        $rows = [];

        foreach ($unmapped as $token => $count) {
            $rows[] = ['##'.$token.'##', $count];
        }

        $io->section('Tokens left unchanged');
        $io->table(['Token', 'Occurrences'], $rows);
        $io->note('These are not Notification Center tokens this importer knows. They were kept as they are -- check whether this bundle offers an equivalent.');
    }
}

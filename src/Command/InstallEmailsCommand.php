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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VTInnovations\CentralizedNotificationSuite\Message\DesignLibrary;
use VTInnovations\CentralizedNotificationSuite\Model\BlockModel;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Model\TemplateModel;

/**
 * Installs ready-made emails: a notification, a layout and a message already composed of
 * blocks, so an editor opens one and changes the wording and pictures rather than starting
 * from an empty HTML field.
 *
 * A command rather than a migration, for the same reason InstallLayoutsCommand is one:
 * writing content into a site's configuration is something an integrator asks for, not
 * something that happens during an upgrade.
 */
#[AsCommand(
    name: 'notification:install-emails',
    description: 'Install ready-made emails (transactional, newsletter, announcement)',
)]
class InstallEmailsCommand extends Command
{
    /**
     * The blocks every preset is built from.
     *
     * Data rather than seeded HTML files: all three families need the same plumbing, and
     * duplicating it per family is how one of them ends up subtly broken. Same reasoning as
     * DesignLibrary::DESIGNS.
     *
     * alias => [title, design, preheader, subject, blocks]
     *
     * @var array<string, array{title: string, design: string, preheader: string, subject: string, blocks: list<array<string, mixed>>}>
     */
    private const EMAILS = [
        'starter-transactional' => [
            'title' => 'Starter: transactional',
            'design' => 'transactional-tinted',
            'preheader' => 'We have received your message',
            'subject' => 'We received your message',
            'blocks' => [
                ['type' => 'heading', 'heading' => 'Thanks, ##name##', 'heading_level' => 'h2'],
                ['type' => 'paragraph', 'body_text' => "We have received your message and will reply shortly.\n\nThere is nothing else you need to do."],
                ['type' => 'details', 'heading' => 'What you sent us', 'details_rows' => [['key' => 'Name', 'value' => '##name##'], ['key' => 'Email', 'value' => '##email##']]],
                ['type' => 'divider'],
                ['type' => 'paragraph', 'body_text' => 'You are receiving this because you contacted us through our website.', 'text_style' => 'muted'],
            ],
        ],
        'starter-newsletter' => [
            'title' => 'Starter: newsletter',
            'design' => 'newsletter',
            'preheader' => 'This month: what we have been working on',
            'subject' => 'The ##month## edition',
            'blocks' => [
                ['type' => 'heading', 'heading' => 'What we have been working on', 'heading_level' => 'h1', 'align' => 'center'],
                ['type' => 'paragraph', 'body_text' => 'A short introduction that tells the reader why this issue is worth their time.', 'text_style' => 'lead', 'align' => 'center'],
                ['type' => 'image', 'image_alt' => 'Replace this with your own picture', 'space_after' => 28],
                ['type' => 'teaser', 'heading' => 'First story', 'body_text' => 'Two or three lines about it, then a link to the full piece.', 'link_text' => 'Read more', 'link_url' => 'https://example.com/', 'teaser_layout' => 'image_left'],
                ['type' => 'teaser', 'heading' => 'Second story', 'body_text' => 'Another short summary. Swap the picture for one of your own.', 'link_text' => 'Read more', 'link_url' => 'https://example.com/', 'teaser_layout' => 'image_right'],
                ['type' => 'button', 'link_text' => 'See everything', 'link_url' => 'https://example.com/', 'align' => 'center', 'space_after' => 28],
                ['type' => 'divider'],
                ['type' => 'paragraph', 'body_text' => 'You are receiving this because you subscribed to our newsletter.', 'text_style' => 'muted', 'align' => 'center'],
            ],
        ],
        'starter-announcement' => [
            'title' => 'Starter: announcement',
            'design' => 'announcement',
            'preheader' => 'Something new from us',
            'subject' => 'Something new from us',
            'blocks' => [
                ['type' => 'image', 'image_alt' => 'Replace this with your own picture'],
                ['type' => 'heading', 'heading' => 'A headline that says the one thing', 'heading_level' => 'h1', 'align' => 'center'],
                ['type' => 'paragraph', 'body_text' => "One short paragraph explaining what changed and why the reader should care.\n\nKeep it to a few lines.", 'text_style' => 'lead', 'align' => 'center'],
                ['type' => 'button', 'link_text' => 'Find out more', 'link_url' => 'https://example.com/', 'align' => 'center'],
            ],
        ],
    ];

    /**
     * Contao's sorting convention: leave room to paste between two rows.
     */
    private const SORTING_STEP = 128;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly DesignLibrary $designs,
    ) {
        parent::__construct();
    }

    /**
     * Exposed so a test can validate the presets against the real block registry without a
     * database.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return self::EMAILS;
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Replace an already installed starter. This DELETES the blocks of its message and recreates them, so any edits to that message are lost.')
            ->addOption('list', null, InputOption::VALUE_NONE, 'Only list what would be installed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        $force = (bool) $input->getOption('force');
        $installed = 0;
        $skipped = [];

        foreach (self::EMAILS as $alias => $preset) {
            if ($input->getOption('list')) {
                $io->writeln(\sprintf(
                    ' * %s <comment>(%s, %d blocks)</comment>',
                    $preset['title'],
                    $alias,
                    \count($preset['blocks']),
                ));

                continue;
            }

            $notification = NotificationModel::findOneBy('alias', $alias);

            if ($notification && !$force) {
                $skipped[] = $preset['title'];

                continue;
            }

            $this->install($alias, $preset, $notification);
            ++$installed;
        }

        if ($input->getOption('list')) {
            return Command::SUCCESS;
        }

        if ($skipped) {
            $io->note(\sprintf(
                'Already installed, left untouched (use --force to replace, which discards edits): %s',
                implode(', ', $skipped),
            ));
        }

        if ($installed > 0) {
            $io->success(\sprintf('Installed %d ready-made email(s). Find them under Centralized Notification Suite > Notifications.', $installed));
            $io->text('Each one has a placeholder recipient and no trigger yet: open its message to set the recipients, then point a form or your own code at its alias.');
        } elseif (!$skipped) {
            $io->info('Nothing to install.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array{title: string, design: string, preheader: string, subject: string, blocks: list<array<string, mixed>>} $preset
     */
    private function install(string $alias, array $preset, NotificationModel|null $notification): void
    {
        $template = $this->template($preset);

        $notification ??= new NotificationModel();
        $notification->tstamp = time();
        $notification->title = $preset['title'];
        $notification->alias = $alias;
        $notification->type = NotificationModel::TYPE_CUSTOM;
        $notification->save();

        $message = MessageModel::findOneBy(['pid=?', 'language=?'], [$notification->id, 'en']) ?? new MessageModel();
        $message->tstamp = time();
        $message->pid = $notification->id;
        $message->language = 'en';
        $message->fallback = '1';
        $message->gateway = (int) (GatewayModel::findOneBy('published', '1')?->id ?? 0);
        $message->subject = $preset['subject'];
        $message->body_mode = MessageModel::BODY_MODE_BLOCKS;
        $message->html = null;
        $message->text = '';
        $message->auto_plaintext = '1';
        $message->template = $template->id;
        $message->embed_images = '1';
        // Deliberately a token rather than a real address: a starter must not be able to send
        // anywhere until someone has chosen a recipient.
        $message->recipients = '##email##';
        $message->priority = 3;
        $message->published = '1';
        $message->save();

        // Blocks have no natural identity of their own, so a replace is a delete and recreate
        foreach (BlockModel::findBy('pid', $message->id) ?? [] as $existing) {
            $existing->delete();
        }

        foreach ($preset['blocks'] as $i => $definition) {
            $block = new BlockModel();
            $block->tstamp = time();
            $block->pid = $message->id;
            $block->sorting = ($i + 1) * self::SORTING_STEP;
            $block->published = '1';
            $block->align = 'left';
            $block->space_after = 20;

            foreach ($definition as $field => $value) {
                $block->$field = \is_array($value) ? serialize($value) : $value;
            }

            $block->save();
        }
    }

    /**
     * The layout the preset's message points at. Design mode rather than stored markup, so
     * the chrome keeps tracking whatever is in the branding record.
     *
     * @param array{title: string, design: string, preheader: string} $preset
     */
    private function template(array $preset): TemplateModel
    {
        $title = $preset['title'].' layout';
        $template = TemplateModel::findOneBy('title', $title) ?? new TemplateModel();

        $template->tstamp = time();
        $template->title = $title;
        $template->layout_mode = 'design';
        // Falling back rather than failing: a renamed design must not stop the install
        $template->design = $this->designs->has($preset['design']) ? $preset['design'] : DesignLibrary::DEFAULT_DESIGN;
        $template->preheader = $preset['preheader'];
        $template->inline_css = '1';
        $template->published = '1';
        $template->save();

        return $template;
    }
}

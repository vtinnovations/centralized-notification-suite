<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VTInnovations\SimpleNotifyBundle\Model\TemplateModel;

/**
 * Installs the bundled starter layouts as tl_simple_template records.
 *
 * A command rather than a migration: writing content into a site's configuration is
 * something an integrator should ask for, not something that happens during an upgrade.
 */
#[AsCommand(
    name: 'simple-notify:install-layouts',
    description: 'Install the bundled starter e-mail layouts',
)]
class InstallLayoutsCommand extends Command
{
    /**
     * File basename => title shown in the backend.
     */
    private const LAYOUTS = [
        'transactional' => 'Transactional (plain, no images)',
        'branded' => 'Branded (logo header, footer)',
    ];

    public function __construct(private readonly ContaoFramework $framework)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite a layout that was already installed')
            ->addOption('list', null, InputOption::VALUE_NONE, 'Only list what would be installed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        $dir = \dirname(__DIR__, 2).'/resources/starter-layouts';
        $force = (bool) $input->getOption('force');
        $installed = 0;
        $skipped = [];

        foreach (self::LAYOUTS as $basename => $title) {
            $html = $dir.'/'.$basename.'.html';
            $css = $dir.'/'.$basename.'.css';

            if (!is_file($html)) {
                $io->warning(\sprintf('Layout file "%s" is missing.', $html));

                continue;
            }

            if ($input->getOption('list')) {
                $io->writeln(\sprintf(' * %s <comment>(%s)</comment>', $title, $basename));

                continue;
            }

            $existing = TemplateModel::findOneBy('title', $title);

            if ($existing && !$force) {
                $skipped[] = $title;

                continue;
            }

            $template = $existing ?? new TemplateModel();
            $template->tstamp = time();
            $template->title = $title;
            $template->layout_mode = 'wrapper';
            $template->wrapper_html = trim(file_get_contents($html) ?: '');
            $template->css = is_file($css) ? trim(file_get_contents($css) ?: '') : '';
            $template->inline_css = '1';
            $template->preheader = '';
            $template->published = '1';
            $template->save();

            ++$installed;
        }

        if ($input->getOption('list')) {
            return Command::SUCCESS;
        }

        if ($skipped) {
            $io->note(\sprintf(
                'Already installed, left untouched (use --force to overwrite): %s',
                implode(', ', $skipped),
            ));
        }

        if ($installed > 0) {
            $io->success(\sprintf('Installed %d layout(s). Find them under Notify > Email layouts.', $installed));
        } else {
            $io->info('Nothing to install.');
        }

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
use Aeon\Automation\Release\FormatterFactory;
use Aeon\Automation\Release\Options;
use Aeon\Automation\Release\ReleaseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogGenerateAll extends AbstractCommand
{
    protected static $defaultName = 'changelog:generate:all';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Generate change log for all tags.')
            ->setHelp('When no parameters are provided, this command will generate changelog for each commit that follows semver semantic.')
            ->addArgument('project', InputArgument::REQUIRED, 'project name, for example aeon-php/calendar')
            ->addOption('tag-start', 'ts', InputOption::VALUE_REQUIRED, 'Generate changelog from given tag, if not provided it starts from the earliest tag')
            ->addOption('tag-end', 'te', InputOption::VALUE_REQUIRED, 'Generate changelog until given tag, if not provided it ends at the last tag')
            ->addOption('tag-skip', 'tsk', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip specific tags')
            ->addOption('skip-from', 'sf', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip changes from given author|authors')
            ->addOption('only-commits', 'oc', InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', 'opr', InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('compare-reverse', 'cpr', InputOption::VALUE_NONE, 'When comparing commits, revers the order and compare start to end, instead end to start.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', 'th', InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Changelog - Generate - All');

        $tags = $this->githubClient()->tags($project)->semVerRsort();
        $tagStart = $input->getOption('tag-start');
        $tagEnd = $input->getOption('tag-end');
        $tagsSkip = (array) $input->getOption('tag-skip');

        if ($tagStart && $tags->exists($tagStart)) {
            $tags = $tags->since($tagStart);
            $io->note('Tag Start: ' . $tagStart);
        }

        if ($tagEnd && $tags->exists($tagEnd)) {
            $tags = $tags->until($tagEnd);
            $io->note('Tag Start: ' . $tagEnd);
        }

        if (\count($tagsSkip)) {
            $tags = $tags->without($tagsSkip);
            $io->note('Tag Skip: ' . \implode(', ', $tagsSkip));
        }

        $io->note('Tags: ' . $tags->count());

        $formatter = (new FormatterFactory($this->configuration()))
            ->create($input->getOption('format'), $input->getOption('theme'));

        foreach ($tags->all() as $tag) {
            $io->title('[' . $tag->name() . ']');

            try {
                $options = new Options(
                    $releaseName = $tag->name(),
                    $commitStart = null,
                    $commitEnd = null,
                    $tagName = $tag->name(),
                    $tagNext = null,
                    $input->getOption('only-commits'),
                    $input->getOption('only-pull-requests'),
                    $input->getOption('compare-reverse'),
                    $changedAfter = null,
                    $changedBefore = null,
                    (array) $input->getOption('skip-from'),
                );

                $releaseService = new ReleaseService($this->configuration(), $options, $this->calendar(), $this->githubClient(), $project);

                $history = $releaseService->fetch();

                $io->note('Total commits: ' . $history->commits()->count());
                $io->progressStart($history->commits()->count());

                $release = $releaseService->analyze($history, function () use ($io) : void {
                    $io->progressAdvance();
                });

                $io->progressFinish();

                $io->note('All commits analyzed, generating changelog: ');
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return Command::FAILURE;
            }

            if (!$release->empty()) {
                $io->write(
                    $formatter
                        ->disableFooter()
                        ->formatRelease($release)
                );
            } else {
                $io->note('No changes');
            }

            $io->newLine();
        }

        if ($tags->count()) {
            $io->write($formatter->formatFooter());
            $io->newLine();
        }

        return Command::SUCCESS;
    }
}

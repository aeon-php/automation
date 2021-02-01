<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Changelog\Manipulator;
use Aeon\Automation\Changelog\Source\EmptySource;
use Aeon\Automation\Changelog\SourceFactory;
use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\File;
use Aeon\Automation\Project;
use Aeon\Automation\Release\FormatterFactory;
use Aeon\Automation\Release\Options;
use Aeon\Automation\Release\ReleaseService;
use Aeon\Automation\Releases;
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
            ->addOption('tag-start', null, InputOption::VALUE_REQUIRED, 'Generate changelog from given tag, if not provided it starts from the earliest tag')
            ->addOption('tag-end', null, InputOption::VALUE_REQUIRED, 'Generate changelog until given tag, if not provided it ends at the last tag')
            ->addOption('tag-skip', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip specific tags')
            ->addOption('tag-only-stable', null, InputOption::VALUE_NONE, 'Check SemVer stability of all tags and remove all unstable')
            ->addOption('skip-from', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip changes from given author|authors')
            ->addOption('only-commits', null, InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', null, InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('compare-reverse', null, InputOption::VALUE_NONE, 'When comparing commits, revers the order and compare start to end, instead end to start.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog')
            ->addOption('github-release-update', null, InputOption::VALUE_NONE, 'Update GitHub release description if you have right permissions and release exists')
            ->addOption('github-file-update-path', null, InputOption::VALUE_REQUIRED, 'Update changelog file directly at GitHub by reading existing file content and changing related release section. For example: <fg=yellow>--github-file-update-path=CHANGELOG.md</>')
            ->addOption('github-file-update-ref', null, InputOption::VALUE_REQUIRED, 'The name of the commit/branch/tag from which to take file for <fg=yellow>--github-file-update-path=CHANGELOG.md</> option. Default: the repository’s default branch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Changelog - Generate - All');

        $tags = $this->githubClient()->tags($project)->rsort();
        $tagStart = $input->getOption('tag-start');
        $tagEnd = $input->getOption('tag-end');
        $tagsSkip = (array) $input->getOption('tag-skip');

        if ($input->getOption('tag-only-stable')) {
            $io->note('Tag Only Stable: true');

            $tags = $tags->onlyStable();
        }

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

        $releases = new Releases();

        foreach (\array_merge([null], $tags->all()) as $tag) {
            $io->title('[' . ($tag === null ? 'Unreleased' : $tag->name()) . ']');

            try {
                $options = new Options(
                    $releaseName = ($tag === null ? 'Unreleased' : $tag->name()),
                    $commitStart = null,
                    $commitEnd = null,
                    $tagName = ($tag === null ? null: $tag->name()),
                    $tagNext = null,
                    $input->getOption('only-commits'),
                    $input->getOption('only-pull-requests'),
                    $input->getOption('compare-reverse'),
                    $changedAfter = null,
                    $changedBefore = null,
                    (array) $input->getOption('skip-from'),
                );

                if ($input->getOption('tag-only-stable')) {
                    $options->tagOnlyStable();
                }

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
                $releases = $releases->add($release);

                if ($input->getOption('github-release-update') && !$release->isUnreleased()) {
                    $githubReleases = $this->githubClient()->releases($project);

                    if (!$githubReleases->exists($release->name())) {
                        $io->error('Release ' . $release->name() . ' not found');

                        return Command::FAILURE;
                    }

                    $io->note('Updating release description...');

                    $this->githubClient()->updateRelease($project, $githubReleases->get($release->name())->id(), $formatter->formatRelease($release));

                    $io->note('Release description updated');
                }
            } else {
                $io->note('No changes');
            }
        }

        $releases = $releases->sort();

        $io->write($formatter->formatReleases($releases));

        $filePath = $input->getOption('github-file-update-path');

        if ($filePath) {
            $fileRef = $input->getOption('github-file-update-ref');

            $io->note('Changelog file: ' . $filePath);
            $io->note('Changelog file ref: ' . ($fileRef ? $fileRef : 'N/A'));

            try {
                $file = $this->githubClient()->file($project, $filePath, $fileRef);
                $source = (new SourceFactory())->create($input->getOption('format'), $file);
            } catch (\Exception $e) {
                $io->note("File \"{$filePath}\" does not exists, it will be created.");
                $file = null;
                $source = new EmptySource();
            }

            $manipulator = new Manipulator();

            $changelogReleases = $manipulator->updateAll($source, $releases)->sort();

            $fileContent = $formatter->formatReleases($changelogReleases);

            $io->note("Updating file {$filePath} content...");

            if ($file === null || ($file instanceof File && $file->hasDifferentContent($fileContent))) {
                $this->githubClient()->putFile(
                    $project,
                    $filePath,
                    'Updated ' . \ltrim($filePath, '/'),
                    'aeon-automation',
                    'automation-bot@aeon-php.org',
                    $fileContent,
                    $file instanceof File ? $file->sha() : null
                );
                $io->note("File {$filePath} content updated.");
                $this->httpCache()->clear();
            } else {
                $io->note('No changes detected, skipping update.');
            }
        }

        return Command::SUCCESS;
    }
}

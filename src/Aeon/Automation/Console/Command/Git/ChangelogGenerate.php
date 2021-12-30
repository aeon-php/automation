<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command\Git;

use Aeon\Automation\Changelog\Manipulator;
use Aeon\Automation\Changelog\Source\EmptySource;
use Aeon\Automation\Changelog\SourceFactory;
use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Git\File;
use Aeon\Automation\Git\RepositoryLocation;
use Aeon\Automation\Release\FormatterFactory;
use Aeon\Automation\Release\Options;
use Aeon\Automation\Release\ReleaseService;
use Aeon\Calendar\Gregorian\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogGenerate extends AbstractCommand
{
    protected static $defaultName = 'git:changelog:generate';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Generate change log for a git repository.')
            ->setHelp('When no parameters are provided, this command will generate Unreleased change log. Please be careful when using --github-release-update and --github-file-update-path since those options will do changes in project repository.')
            ->addArgument('repository', InputArgument::OPTIONAL, 'local path to repository', '.')
            ->addOption('commit-start', null, InputOption::VALUE_REQUIRED, 'Optional commit sha from which changelog is generated . When not provided, default branch latest commit is taken')
            ->addOption('commit-end', null, InputOption::VALUE_REQUIRED, 'Optional commit sha until which changelog is generated . When not provided, latest tag is taken')
            ->addOption('changed-after', null, InputOption::VALUE_REQUIRED, 'Ignore all changes after given date, relative date formats like "-1 day" are also supported')
            ->addOption('changed-before', null, InputOption::VALUE_REQUIRED, 'Ignore all changes before given date, relative date formats like "-1 day" are also supported')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'From this branch commit start will be taken when --commit-start option is not provided')
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'List only changes from given release')
            ->addOption('tag-next', null, InputOption::VALUE_REQUIRED, 'List only changes until given release')
            ->addOption('tag-only-stable', null, InputOption::VALUE_NONE, 'Check SemVer stability of all tags and remove all unstable')
            ->addOption('release-name', null, InputOption::VALUE_REQUIRED, 'Name of the release when --tag option is not provided', 'Unreleased')
            ->addOption('compare-reverse', null, InputOption::VALUE_NONE, 'When comparing commits, revers the order and compare start to end, instead end to start.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog')
            ->addOption('skip-from', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip changes from given author|authors')
            ->addOption('file-update-path', null, InputOption::VALUE_REQUIRED, 'Update changelog file directly by reading existing file content and changing related release section. For example: <fg=yellow>--file-update-path=CHANGELOG.md</>');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $location = new RepositoryLocation($input->getArgument('repository'));

        $io->title('Changelog - Generate');

        try {
            $options = new Options(
                $input->getOption('tag') ? $input->getOption('tag') : $input->getOption('release-name'),
                $input->getOption('branch'),
                $input->getOption('commit-start'),
                $input->getOption('commit-end'),
                $input->getOption('tag'),
                $input->getOption('tag-next'),
                $onlyCommits = true,
                $onlyPullRequests = false,
                $input->getOption('compare-reverse'),
                $input->getOption('changed-after') ? DateTime::fromString($input->getOption('changed-after')) : null,
                $input->getOption('changed-before') ? DateTime::fromString($input->getOption('changed-before')) : null,
                (array) $input->getOption('skip-from'),
            );

            if ($input->getOption('tag-only-stable')) {
                $options->tagOnlyStable();
            }

            $releaseService = new ReleaseService($this->configuration(), $options, $this->calendar(), $this->git($location));

            $history = $releaseService->fetch();
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->note('Release: ' . $options->releaseName());
        $io->note('Location: ' . $location->toString());
        $io->note('Format: ' . $input->getOption('format'));
        $io->note('Theme: ' . $input->getOption('theme'));

        if ($history->scope()->branch()) {
            $io->note('Branch: ' . $history->scope()->branch()->name());
        }

        if ($history->scope()->tagStart()) {
            $io->note('Tag Start: ' . $history->scope()->tagStart()->tagName());
        }

        if ($history->scope()->tagEnd()) {
            $io->note('Tag End: ' . $history->scope()->tagEnd()->tagName());
        }

        if ($options->compareReverse() && $history->scope()->isFull()) {
            $io->note('Reversed Start with End commit');
        }

        if ($history->scope() !== null) {
            $io->note('Commit Start: ' . $history->scope()->commitStart()->sha() . ($options->compareReverse() ? ' - reversed' : ''));
        }

        if ($history->scope()->commitEnd() !== null) {
            $io->note('Commit End: ' . $history->scope()->commitEnd()->sha() . ($options->compareReverse() ? ' - reversed' : ''));
        }

        if ($options->changedAfter()) {
            $io->note('Changes After: ' . $options->changedAfter()->toISO8601());
        }

        if ($options->changedBefore()) {
            $io->note('Changes Before: ' . $options->changedBefore()->toISO8601());
        }

        if (\count($options->skipAuthors())) {
            $io->note('Skip from: @' . \implode(', @', $options->skipAuthors()));
        }

        try {
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

        $formatter =  (new FormatterFactory($this->configuration()))->create($input->getOption('format'), $input->getOption('theme'));

        if (!$release->empty()) {
            $io->write($formatter->formatRelease($release));

            $filePath = $input->getOption('file-update-path');

            if ($filePath) {
                $io->note('Changelog file: ' . $filePath);

                try {
                    $file = $this->git($location)->file($filePath, null);
                    $source = (new SourceFactory())->create($input->getOption('format'), $file);
                } catch (\Exception $e) {
                    $io->note("File \"{$filePath}\" does not exists, it will be created.");
                    $file = null;
                    $source = new EmptySource();
                }

                $manipulator = new Manipulator();

                $changelogReleases = $manipulator->update($source, $release)->sort();

                $fileContent = $formatter->formatReleases($changelogReleases);

                $io->note("Updating file {$filePath} content...");

                if ($file === null || ($file instanceof File && $file->hasDifferentContent($fileContent))) {
                    $this->git($location)->putFile(
                        $filePath,
                        'Updated ' . \ltrim(\basename($filePath), '/'),
                        $this->configuration()->commiterName(),
                        $this->configuration()->commiterEmail(),
                        $fileContent,
                    );
                    $io->note("File {$filePath} content updated.");

                    $this->httpCache()->clear();
                } else {
                    $io->note('No changes detected, skipping update.');
                }
            }
        } else {
            $io->note('No changes');
        }

        return Command::SUCCESS;
    }
}

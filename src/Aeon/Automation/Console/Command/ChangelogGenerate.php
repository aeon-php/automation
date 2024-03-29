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
use Aeon\Calendar\Gregorian\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogGenerate extends AbstractCommand
{
    protected static $defaultName = 'changelog:generate';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Generate change log for a release.')
            ->setHelp('When no parameters are provided, this command will generate Unreleased change log. Please be careful when using --github-release-update and --github-file-update-path since those options will do changes in project repository.')
            ->addArgument('project', InputArgument::REQUIRED, 'project name, for example aeon-php/calendar')
            ->addOption('commit-start', null, InputOption::VALUE_REQUIRED, 'Optional commit sha from which changelog is generated . When not provided, default branch latest commit is taken')
            ->addOption('commit-end', null, InputOption::VALUE_REQUIRED, 'Optional commit sha until which changelog is generated . When not provided, latest tag is taken')
            ->addOption('changed-after', null, InputOption::VALUE_REQUIRED, 'Ignore all changes after given date, relative date formats like "-1 day" are also supported')
            ->addOption('changed-before', null, InputOption::VALUE_REQUIRED, 'Ignore all changes before given date, relative date formats like "-1 day" are also supported')
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'List only changes from given release')
            ->addOption('tag-next', null, InputOption::VALUE_REQUIRED, 'List only changes until given release')
            ->addOption('tag-only-stable', null, InputOption::VALUE_NONE, 'Check SemVer stability of all tags and remove all unstable')
            ->addOption('release-name', null, InputOption::VALUE_REQUIRED, 'Name of the release when --tag option is not provided', 'Unreleased')
            ->addOption('only-commits', null, InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', null, InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('compare-reverse', null, InputOption::VALUE_NONE, 'When comparing commits, revers the order and compare start to end, instead end to start.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog')
            ->addOption('skip-from', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip changes from given author|authors')
            ->addOption('github-release-update', null, InputOption::VALUE_NONE, 'Update GitHub release description if you have right permissions and release exists')
            ->addOption('github-file-update-path', null, InputOption::VALUE_REQUIRED, 'Update changelog file directly at GitHub by reading existing file content and changing related release section. For example: <fg=yellow>--github-file-update-path=CHANGELOG.md</>')
            ->addOption('github-file-update-ref', null, InputOption::VALUE_REQUIRED, 'The name of the commit/branch/tag from which to take file for <fg=yellow>--github-file-update-path=CHANGELOG.md</> option. Default: the repository’s default branch.')
            ->addOption('file-update-path', null, InputOption::VALUE_REQUIRED, 'Update changelog file by reading local file content and changing related release section. For example: <fg=yellow>--file-update-path=./CHANGELOG.md</>');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Changelog - Generate');

        try {
            $options = new Options(
                $input->getOption('tag') ? $input->getOption('tag') : $input->getOption('release-name'),
                $input->getOption('commit-start'),
                $input->getOption('commit-end'),
                $input->getOption('tag'),
                $input->getOption('tag-next'),
                $input->getOption('only-commits'),
                $input->getOption('only-pull-requests'),
                $input->getOption('compare-reverse'),
                $input->getOption('changed-after') ? DateTime::fromString($input->getOption('changed-after')) : null,
                $input->getOption('changed-before') ? DateTime::fromString($input->getOption('changed-before')) : null,
                (array) $input->getOption('skip-from'),
            );

            if ($input->getOption('tag-only-stable')) {
                $options->tagOnlyStable();
            }

            $releaseService = new ReleaseService($this->configuration(), $options, $this->calendar(), $this->githubClient(), $project);

            $history = $releaseService->fetch();
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->note('Release: ' . $options->releaseName());
        $io->note('Project: ' . $project->fullName());
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

            if ($input->getOption('github-release-update') && !$release->isUnreleased()) {
                $remoteReleases = $this->githubClient()->releases($project);

                if (!$remoteReleases->exists($release->name())) {
                    $io->error('Release ' . $release->name() . ' not found');

                    return Command::FAILURE;
                }

                $io->note('Updating release description...');

                $this->githubClient()->updateRelease($project, $remoteReleases->get($release->name())->id(), $formatter->formatRelease($release));

                $io->note('Release description updated');
            }

            $githubFilePath = $input->getOption('github-file-update-path');

            if ($githubFilePath) {
                $fileRef = $input->getOption('github-file-update-ref');

                $io->note('Changelog GitHub file: ' . $githubFilePath);
                $io->note('Changelog GitHub file ref: ' . ($fileRef ? $fileRef : 'N/A'));

                try {
                    $githubFile = $this->githubClient()->file($project, $githubFilePath, $fileRef);
                    $source = (new SourceFactory())->create($input->getOption('format'), $githubFile);
                } catch (\Exception $e) {
                    $io->note("File \"{$githubFilePath}\" does not exists, it will be created.");
                    $githubFile = null;
                    $source = new EmptySource();
                }

                $manipulator = new Manipulator();

                $changelogReleases = $manipulator->update($source, $release)->sort();

                $githubFileContent = $formatter->formatReleases($changelogReleases);

                $io->note("Updating file {$githubFilePath} content...");

                if ($githubFile === null || ($githubFile instanceof File && $githubFile->hasDifferentContent($githubFileContent))) {
                    $this->githubClient()->putFile(
                        $project,
                        $githubFilePath,
                        'Updated ' . \ltrim($githubFilePath, '/'),
                        $this->configuration()->commiterName(),
                        $this->configuration()->commiterEmail(),
                        $githubFileContent,
                        $githubFile instanceof File ? $githubFile->sha() : null
                    );
                    $io->note("File {$githubFilePath} content updated.");

                    $this->httpCache()->clear();
                } else {
                    $io->note('No changes detected, skipping update.');
                }
            }

            $localFilePath = $input->getOption('file-update-path');

            if ($localFilePath) {
                $io->note('Changelog local file: ' . $localFilePath);

                try {
                    $localFile = File::fromLocalFile($localFilePath);
                    $source = (new SourceFactory())->create($input->getOption('format'), $localFile);
                } catch (\Exception $e) {
                    $io->note("File \"{$localFilePath}\" does not exists, it will be created.");
                    $localFile = null;
                    $source = new EmptySource();
                }

                $manipulator = new Manipulator();

                $changelogReleases = $manipulator->update($source, $release)->sort();

                $localFileContent = $formatter->formatReleases($changelogReleases);

                $io->note("Updating file {$localFilePath} content...");

                if ($localFile === null || ($localFile instanceof File && $localFile->hasDifferentContent($localFileContent))) {
                    \file_put_contents($localFilePath, $localFileContent);
                    $io->note("File {$localFilePath} content updated.");

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

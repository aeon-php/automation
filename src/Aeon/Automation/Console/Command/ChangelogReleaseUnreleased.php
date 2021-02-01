<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Changelog\Manipulator;
use Aeon\Automation\Changelog\SourceFactory;
use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\File;
use Aeon\Automation\Project;
use Aeon\Automation\Release\FormatterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogReleaseUnreleased extends AbstractCommand
{
    protected static $defaultName = 'changelog:release:unreleased';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Update changelog file by turning Unreleased section into the next release')
            ->setHelp('This command only manipulates the changelog file, it does not create new releases.')
            ->addArgument('project', InputArgument::REQUIRED, 'project name, for example aeon-php/calendar')
            ->addArgument('changelog-file-path', InputArgument::REQUIRED, 'Path to the changelog file from repository root. For example: <fg=yellow>CHANGELOG.md</>')
            ->addArgument('release-name', InputArgument::REQUIRED, 'Name of the next release.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog')
            ->addOption('github-release-update', null, InputOption::VALUE_NONE, 'Update GitHub release description if you have right permissions and release exists')
            ->addOption('github-file-changelog-update', null, InputOption::VALUE_NONE, 'Update changelog file by pushing commit to GitHub directly')
            ->addOption('github-file-update-ref', null, InputOption::VALUE_REQUIRED, 'The name of the commit/branch/tag from which to take file for <fg=yellow>changelog-file-path</> argument. Default: the repository’s default branch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Changelog - Release Unreleased');

        $tags = $this->githubClient()->tags($project);
        $releaseName = $input->getArgument('release-name');
        $fileRef = $input->getOption('github-file-update-ref');
        $filePath = $input->getArgument('changelog-file-path');

        try {
            $file = $this->githubClient()->file($project, $filePath, $fileRef);
            $source = (new SourceFactory())->create($input->getOption('format'), $file);
        } catch (\Exception $e) {
            $io->error("File \"{$filePath}\" does not exists in repository.");

            return Command::FAILURE;
        }

        if (!$tags->exists($releaseName)) {
            $io->error("Tag {$releaseName} does not exists.");

            return Command::FAILURE;
        }

        $io->note('Changelog file: ' . $filePath);
        $io->note('Changelog file ref: ' . ($fileRef ? $fileRef : 'N/A'));

        try {
            $manipulator = new Manipulator();
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $changelogReleases = $manipulator->release($source, $releaseName, $this->calendar()->currentDay())->sort();

        $formatter = (new FormatterFactory($this->configuration()))->create($input->getOption('format'), $input->getOption('theme'));

        $fileContent = $formatter->formatReleases($changelogReleases);

        $io->write($fileContent);

        if ($input->getOption('github-release-update')) {
            $remoteReleases = $this->githubClient()->releases($project);

            if (!$remoteReleases->exists($releaseName)) {
                $io->error('Release ' . $releaseName . ' not found, please release new version before moving forward.');

                return Command::FAILURE;
            }

            $remoteReleases = $this->githubClient()->releases($project);

            $io->note('Updating release description...');

            $this->githubClient()->updateRelease($project, $remoteReleases->get($releaseName)->id(), $formatter->formatRelease($changelogReleases->get($releaseName)));

            $io->note('Release description updated');
        }

        if ($input->getOption('github-file-changelog-update')) {
            $io->note("Updating file {$filePath} content...");

            if ($file instanceof File && $file->hasDifferentContent($fileContent)) {
                $this->githubClient()->putFile(
                    $project,
                    $filePath,
                    'Updated ' . \ltrim($filePath, '/'),
                    $this->configuration()->commiterName(),
                    $this->configuration()->commiterEmail(),
                    $fileContent,
                    $file->sha()
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

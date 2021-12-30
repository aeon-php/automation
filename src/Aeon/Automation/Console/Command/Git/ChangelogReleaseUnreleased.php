<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command\Git;

use Aeon\Automation\Changelog\Manipulator;
use Aeon\Automation\Changelog\SourceFactory;
use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Git\File;
use Aeon\Automation\Git\RepositoryLocation;
use Aeon\Automation\Release\FormatterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogReleaseUnreleased extends AbstractCommand
{
    protected static $defaultName = 'git:changelog:release:unreleased';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Update Git repository changelog file by turning Unreleased section into the next release')
            ->setHelp('This command only manipulates the changelog file, it does not create new releases.')
            ->addArgument('changelog-file-path', InputArgument::REQUIRED, 'Path to the changelog file from repository root. For example: <fg=yellow>CHANGELOG.md</>')
            ->addArgument('release-name', InputArgument::REQUIRED, 'Name of the next release.')
            ->addArgument('repository', InputArgument::OPTIONAL, 'local path to repository', '.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog')
            ->addOption('file-changelog-update', null, InputOption::VALUE_NONE, 'Update changelog file by pushing commit to GitHub directly');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $repositoryLocation = new RepositoryLocation($input->getArgument('repository'));

        $io->title('Changelog - Release Unreleased');

        $tags = $this->git($repositoryLocation)->tags();
        $releaseName = $input->getArgument('release-name');
        $filePath = $input->getArgument('changelog-file-path');

        try {
            $file = $this->git($repositoryLocation)->file($filePath);
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

        if ($input->getOption('file-changelog-update')) {
            $io->note("Updating file {$filePath} content...");

            if ($file instanceof File && $file->hasDifferentContent($fileContent)) {
                $this->git($repositoryLocation)->putFile(
                    $filePath,
                    'Updated ' . \ltrim(\basename($filePath), '/'),
                    $this->configuration()->commiterName(),
                    $this->configuration()->commiterEmail(),
                    $fileContent
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

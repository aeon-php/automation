<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogGet extends AbstractCommand
{
    protected static $defaultName = 'changelog:get';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Get project changelog.')
            ->setHelp('When no parameters are provided, this command will generate Unreleased change log. Please be careful when using --github-release-update and --github-file-update-path since those options will do changes in project repository.')
            ->addArgument('project', InputArgument::REQUIRED, 'project name, for example aeon-php/calendar')
            ->addOption('github-file-path', null, InputOption::VALUE_REQUIRED, 'changelog file path', 'CHANGELOG.md')
            ->addOption('github-file-ref', null, InputOption::VALUE_REQUIRED, 'The name of the commit/branch/tag from which to take file for <fg=yellow>--github-file-path=CHANGELOG.md</> option. Default: the repository’s default branch.')
            ->addOption('sha1-hash', null, InputOption::VALUE_NONE, 'Optional display only sha1 hash of the changelog file instead of file content');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Changelog - Get');
        $fileRef = $input->getOption('github-file-ref');
        $filePath = $input->getOption('github-file-path');

        try {
            $io->note('File Path: ' . $filePath);
            $io->note('File Ref: ' . $fileRef);

            $file = $this->githubClient($project)->file($filePath, $fileRef);

            if ($input->getOption('sha1-hash')) {
                $io->write(\sha1($file->content()));

                return Command::SUCCESS;
            }

            $io->write($file->content());

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("File {$filePath} not found.");

            return Command::FAILURE;
        }
    }
}

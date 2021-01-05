<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Changes\ChangesParser\HTMLChangesParser;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\PullRequest;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PullRequestDescriptionCheck extends AbstractCommand
{
    protected static $defaultName = 'pull-request:description:check';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Check if pull request has changes in expected by Automation format.')
            ->setHelp('Expected format can be taken from <fg=yellow>pull-request:template:show</> command')
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addArgument('number', InputArgument::REQUIRED, 'pull request number')
            ->addOption('skip-changes-count', 'scf', InputOption::VALUE_OPTIONAL, 'Skip check if the changes count is greater than 0, syntax is still checked')
            ->addOption('skip-from', 'sf', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip check when pull request comes from author|authors');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $number = (int) $input->getArgument('number');
        $skipAuthors = \array_map(fn (string $author) : string => \strtolower($author), (array) $input->getOption('skip-from'));
        $skipChangesCount = $input->getOption('skip-changes-count');

        $io->title('Pull Request - Description - Check');

        $io->note('Number: #' . $number);

        if (\count($skipAuthors)) {
            $io->note('Skipped Authors: @' . \implode(', @', $skipAuthors));
        }

        try {
            $pullRequest = PullRequest::fromNumber($this->github(), $project, $number);
        } catch (RuntimeException $exception) {
            $io->error("Pull request #{$number} not found.");

            return Command::FAILURE;
        }

        $io->note('Url: ' . $pullRequest->url());
        $io->note('Author: @' . $pullRequest->user());
        $io->note('Date: ' . $pullRequest->date()->toISO8601());

        foreach ($skipAuthors as $skipAuthor) {
            if ($skipAuthor === \strtolower($pullRequest->user())) {
                $io->success('Skipping syntax check because of the author.');

                return Command::SUCCESS;
            }
        }

        $htmlChangeParser = new HTMLChangesParser();

        if (!$htmlChangeParser->support($pullRequest)) {
            $io->error('Invalid Pull Request syntax.');

            return Command::FAILURE;
        }

        $changes = $htmlChangeParser->parse($pullRequest);

        if ($skipChangesCount == false && !$changes->count()) {
            $io->error('Pull Request syntax is valid but it\'s empty.');

            return Command::FAILURE;
        }

        $io->success('Detected changes: ' . $changes->count());

        return Command::SUCCESS;
    }
}

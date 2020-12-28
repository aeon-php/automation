<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\GitHub\PullRequests;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class PullRequestsList extends AbstractCommand
{
    protected static $defaultName = 'pull-request:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'One of the given states: open, merged', 'open')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Get the the branch used instead of tag-start option when it\'s not provided. If empty, default repository branch is taken.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Pull Request - List');

        $repository = Repository::create($this->github(), $project);
        $branchName = $input->getOption('branch') !== null ? $input->getOption('branch') : $repository->defaultBranch();
        $status = $input->getOption('status');

        $io->note('Branch: ' . $branchName);
        $io->note('Status: ' . $status);

        if (!\in_array($status, ['open', 'merged'], true)) {
            $io->error('Invalid status: ' . $status);

            return Command::FAILURE;
        }

        try {
            Reference::commitFromString($this->github(), $project, 'heads/' . $branchName);
        } catch (RuntimeException $e) {
            $io->error('Branch "heads/' . $branchName . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $closedPullRequests = $status === 'open'
            ? PullRequests::allOpenFor($this->github(), $project, $branchName)
            : PullRequests::allClosedFor($this->github(), $project, $branchName)->onlyMerged();

        foreach ($closedPullRequests->all() as $pullRequest) {
            if (!$pullRequest->hasMilestone()) {
                $io->note('Number: #' . $pullRequest->id());
                $io->warning('Milestone is missing');
                $io->note('URL: ' . $pullRequest->url());
            } else {
                $io->note('Number: #' . $pullRequest->id() . ' - ' . $pullRequest->title());
            }
        }

        $io->note('Total count: ' . $closedPullRequests->count());

        return Command::SUCCESS;
    }
}

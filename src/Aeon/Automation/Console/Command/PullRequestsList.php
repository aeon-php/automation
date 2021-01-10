<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\PullRequests;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Aeon\Automation\Project;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PullRequestsList extends AbstractCommand
{
    protected static $defaultName = 'pull-request:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'One of the given states: open, merged', 'open')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Get the the branch used instead of tag-start option when it\'s not provided. If empty, default repository branch is taken.')
            ->addOption('check-milestone', 'cm', InputOption::VALUE_NONE, 'Check also if the pull request is missing a milestone')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit of pull requests to display', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Pull Request - List');

        $repository = Repository::fromProject($this->github(), $project);
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

        $pullRequests = $status === 'open'
            ? PullRequests::allOpenFor($this->github(), $project, $branchName, (int) $input->getOption('limit'))
            : PullRequests::allClosedFor($this->github(), $project, $branchName, (int) $input->getOption('limit'))->onlyMerged();

        foreach ($pullRequests->all() as $pullRequest) {
            $pullRequestOutput = '#' . $pullRequest->id() . ' - ' . $pullRequest->description();

            if ($input->getOption('check-milestone')) {
                if (!$pullRequest->hasMilestone()) {
                    $pullRequestOutput .= ' - <fg=yellow>missing milestone</>';
                }
            }

            $pullRequestOutput .= ' - ' . $pullRequest->url();

            $io->writeln($pullRequestOutput);
        }

        $io->note('Total count: ' . $pullRequests->count());

        return Command::SUCCESS;
    }
}

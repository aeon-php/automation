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

final class PullRequestsList extends AbstractCommand
{
    protected static $defaultName = 'pull-request:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'One of the given states: open, merged', 'open')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'Get the the branch used instead of tag-start option when it\'s not provided. If empty, default repository branch is taken.')
            ->addOption('check-milestone', null, InputOption::VALUE_NONE, 'Check also if the pull request is missing a milestone')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit of pull requests to display', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Pull Request - List');

        $repository = $this->githubClient()->repository($project);
        $branchName = $input->getOption('branch') !== null ? $input->getOption('branch') : $repository->defaultBranch();
        $status = $input->getOption('status');

        $io->note('Branch: ' . $branchName);
        $io->note('Status: ' . $status);

        if (!\in_array($status, ['open', 'merged'], true)) {
            $io->error('Invalid status: ' . $status);

            return Command::FAILURE;
        }

        try {
            $this->githubClient()->branch($project, $branchName);
        } catch (\Exception $e) {
            $io->error('Branch "heads/' . $branchName . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $pullRequests = $status === 'open'
            ? $this->githubClient()->pullRequestsOpen($project, $branchName, (int) $input->getOption('limit'))
            : $this->githubClient()->pullRequestsClosed($project, $branchName, (int) $input->getOption('limit'))->onlyMerged();

        foreach ($pullRequests->all() as $pullRequest) {
            $pullRequestOutput = '#' . $pullRequest->number() . ' - ' . $pullRequest->description();

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

<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Configuration;
use Aeon\Automation\GitHub\PullRequests;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Github\Client;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class PullRequestsList extends Command
{
    protected static $defaultName = 'pull-request:list';

    private array $defaultConfigPaths;

    private Client $github;

    public function __construct(Client $github, array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->defaultConfigPaths = $defaultConfigPaths;
        $this->github = $github;
    }

    protected function configure() : void
    {
        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'One of the given states: open, merged', 'open')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Get the the branch used instead of tag-start option when it\'s not provided. If empty, default repository branch is taken.')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));

        if ($configuration->githubAccessToken()) {
            $this->github->authenticate($configuration->githubAccessToken(), null, Client::AUTH_ACCESS_TOKEN);
        }

        $project = $configuration->project($input->getArgument('project'));

        $io->title('Pull Request - List');

        $repository = Repository::create($this->github, $project);
        $branchName = $input->getOption('branch') !== null ? $input->getOption('branch') : $repository->defaultBranch();
        $status = $input->getOption('status');

        $io->note('Branch: ' . $branchName);
        $io->note('Status: ' . $status);

        if (!\in_array($status, ['open', 'merged'], true)) {
            $io->error('Invalid status: ' . $status);

            return Command::FAILURE;
        }

        try {
            Reference::commitFromString($this->github, $project, 'heads/' . $branchName);
        } catch (RuntimeException $e) {
            $io->error('Branch "heads/' . $branchName . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $closedPullRequests = $status === 'open'
            ? PullRequests::allOpenFor($this->github, $project, $branchName)
            : PullRequests::allClosedFor($this->github, $project, $branchName)->onlyMerged();

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

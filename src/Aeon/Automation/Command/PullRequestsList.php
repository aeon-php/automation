<?php

declare(strict_types=1);

namespace Aeon\Automation\Command;

use Aeon\Automation\Configuration;
use Github\Client;
use Github\ResultPager;
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

    public function __construct(array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->defaultConfigPaths = $defaultConfigPaths;
    }

    protected function configure() : void
    {
        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $client = new Client();
        $client->authenticate(\getenv('AEON_AUTOMATION_GH_TOKEN'), null, Client::AUTH_ACCESS_TOKEN);

        $configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));
        $project = $configuration->project($input->getArgument('project'));

        $paginator  = new ResultPager($client);
        $closedPullRequests = $paginator->fetchAll($client->api('pull_request'), 'all', [$project->organization(), $project->name(), ['state' => 'closed']]);

        foreach ($closedPullRequests as $pullRequest) {
            if ($pullRequest['merged_at'] === null && $pullRequest['closed_at'] !== null) {
                continue;
            }

            if (!isset($pullRequest['milestone'])) {
                $io->note('Number: #' . $pullRequest['number']);
                $io->warning('Milestone is missing');
                $io->note('URL: ' . $pullRequest['html_url']);
            } else {
                $io->note('Number: #' . $pullRequest['number'] . ' - ' . $pullRequest['milestone']['title']);
            }
        }

        $io->note('Total count: ' . \count($closedPullRequests));

        return Command::SUCCESS;
    }
}

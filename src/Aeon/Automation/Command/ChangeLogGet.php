<?php

declare(strict_types=1);

namespace Aeon\Automation\Command;

use Aeon\Automation\ChangeLog;
use Aeon\Automation\Configuration;
use Aeon\Automation\PullRequest;
use Aeon\Calendar\Gregorian\GregorianCalendar;
use Github\Client;
use Github\ResultPager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ChangeLogGet extends Command
{
    protected static $defaultName = 'change-log:get';

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
            ->addArgument('milestone', InputArgument::REQUIRED, 'milestone name')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'format used to display the changelog')
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

        $io->title('Milestone Changelog');

        $changelog = new ChangeLog($input->getArgument('milestone'), GregorianCalendar::UTC()->now()->day());

        foreach ($closedPullRequests as $pullRequestData) {
            if ($pullRequestData['merged_at'] === null && $pullRequestData['closed_at'] !== null) {
                continue;
            }

            if (isset($pullRequestData['milestone']) && $pullRequestData['milestone']['title'] === $input->getArgument('milestone')) {
                $pullRequest = new PullRequest(
                    (string) $pullRequestData['number'],
                    $pullRequestData['html_url'],
                    $pullRequestData['title'],
                    $pullRequestData['user']['login'],
                    $pullRequestData['user']['html_url'],
                    $pullRequestData['body']
                );

                if (!$pullRequest->haveHTML() || !$pullRequest->haveChangesDescription()) {
                    $io->note('Pull Request #' . $pullRequestData['number'] . ' does not have a changelog section, it will be added into changed section.');
                }

                $changelog->add($pullRequest->changes());
            }
        }

        $formatter = new ChangeLog\MarkdownFormatter();

        $io->write($formatter->format($changelog));

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Command;

use Aeon\Automation\Configuration;
use Composer\Semver\Semver;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MilestoneList extends Command
{
    protected static $defaultName = 'milestone:list';

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
            ->addOption('create-missing', 'cm', InputOption::VALUE_NONE, 'Create missing milestones for existing releases')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $client = new Client();
        $client->authenticate(\getenv('AEON_AUTOMATION_GH_TOKEN'), null, Client::AUTH_ACCESS_TOKEN);

        $configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));
        $project = $configuration->project($input->getArgument('project'));

        $milestones = $client->api('issue')->milestones()->all($project->organization(), $project->name(), ['state' => 'all']);
        $releases = $client->api('repo')->releases()->all($project->organization(), $project->name());

        $io->title($project->organization());

        $io->block('Milestones:');

        $milestoneTitles = Semver::sort(\array_map(fn (array $milestoneData) => $milestoneData['title'], $milestones));
        $releaseNames = Semver::sort(\array_map(fn (array $releaseData) => $releaseData['name'], $releases));

        foreach ($milestoneTitles as $milestoneTitle) {
            $io->writeln(' - ' . $milestoneTitle);
        }

        $io->newLine();

        $io->block('Releases:');

        foreach ($releaseNames as $releaseName) {
            $io->writeln(' - ' . $releaseName);

            if (!\in_array($releaseName, $milestoneTitles, true)) {
                $io->warning('Missing milestone: ' . $releaseName);

                if ($input->getOption('create-missing') === true) {
                    $io->note('Creating milestone: ' . $releaseName);
                    $milestone = $client->api('issue')->milestones()->create($project->organization(), $project->name(), ['title' => $releaseName]);
                }
            }
        }

        $latestMilestone = \end($milestoneTitles);
        $unreleasedMilestones = \array_diff($milestoneTitles, $releaseNames);

        $io->note('Latest Milestone: ' . $latestMilestone);

        foreach ($unreleasedMilestones as $unreleasedMilestone) {
            $io->note('Unreleased: ' . $unreleasedMilestone);
        }

        return Command::SUCCESS;
    }
}

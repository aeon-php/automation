<?php

declare(strict_types=1);

namespace Aeon\Automation\Command;

use Aeon\Automation\Configuration;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MilestoneCreate extends Command
{
    protected static $defaultName = 'milestone:create';

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
            ->addArgument('milestone', InputArgument::REQUIRED, 'new milestone version')
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

        $io->title($project->name());

        $milestoneTitles = Semver::sort(\array_map(fn (array $milestoneData) => $milestoneData['title'], $milestones));

        $newMilestone = $input->getArgument('milestone');

        if (\in_array($newMilestone, $milestoneTitles, true)) {
            $io->error('Milestone already exists');

            return Command::FAILURE;
        }

        $parser = new VersionParser();

        try {
            $parser->normalize($newMilestone);
        } catch (\UnexpectedValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $client->api('issue')->milestones()->create($project->organization(), $project->name(), ['title' => $newMilestone]);

        $io->success('Milestone created');

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MilestoneCreate extends AbstractCommand
{
    protected static $defaultName = 'milestone:create';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Create new milestone for project')
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addArgument('milestone', InputArgument::REQUIRED, 'new milestone version');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Milestone - Create');

        $milestones = $this->github()->issue()->milestones()->all($project->organization(), $project->name(), ['state' => 'all']);

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

        $this->github()->issue()->milestones()->create($project->organization(), $project->name(), ['title' => $newMilestone]);

        $io->success('Milestone created');

        return Command::SUCCESS;
    }
}

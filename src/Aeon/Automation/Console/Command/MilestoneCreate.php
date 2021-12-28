<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
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

        $milestones = $this->githubClient($project)->milestones()->semVerSort();

        $io->title($project->name());

        $newMilestone = $input->getArgument('milestone');

        if ($milestones->exists($newMilestone)) {
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

        $this->githubClient($project)->createMilestone($newMilestone);

        $io->success('Milestone created');

        return Command::SUCCESS;
    }
}

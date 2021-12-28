<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MilestoneList extends AbstractCommand
{
    protected static $defaultName = 'milestone:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $milestones = $this->githubClient($project)->milestones()->semVerRsort();
        $releases = $this->githubClient($project)->releases()->semVerRsort();

        $io->title('Milestone - List');

        $io->note('Milestones:');

        foreach ($milestones->all() as $milestone) {
            $milestoneOutput = $milestone->title();

            if (!$releases->exists($milestone->title())) {
                $milestoneOutput .= ' - <fg=yellow>unreleased</>';
            }

            $io->writeln($milestoneOutput);
        }

        return Command::SUCCESS;
    }
}

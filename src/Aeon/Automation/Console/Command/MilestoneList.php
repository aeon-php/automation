<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
use Composer\Semver\Semver;
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

        $milestones = $this->github()->issues()->milestones()->all($project->organization(), $project->name(), ['state' => 'all']);
        $releases = $this->github()->repository()->releases()->all($project->organization(), $project->name());

        $io->title('Milestone - List');

        $io->note('Milestones:');

        $milestoneTitles = Semver::rsort(\array_map(fn (array $milestoneData) => $milestoneData['title'], $milestones));
        $releaseNames = Semver::rsort(\array_map(fn (array $releaseData) => $releaseData['name'], $releases));

        $unreleasedMilestones = \array_diff($milestoneTitles, $releaseNames);

        foreach ($milestoneTitles as $milestoneTitle) {
            $milestoneOutput = $milestoneTitle;

            if (\in_array($milestoneTitle, $unreleasedMilestones, true)) {
                $milestoneOutput .= ' - <fg=yellow>unreleased</>';
            }

            $io->writeln($milestoneOutput);
        }

        return Command::SUCCESS;
    }
}

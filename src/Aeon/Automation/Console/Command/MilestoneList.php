<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Composer\Semver\Semver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MilestoneList extends AbstractCommand
{
    protected static $defaultName = 'milestone:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('create-missing', 'cm', InputOption::VALUE_NONE, 'Create missing milestones for existing releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $milestones = $this->github()->issues()->milestones()->all($project->organization(), $project->name(), ['state' => 'all']);
        $releases = $this->github()->repository()->releases()->all($project->organization(), $project->name());

        $io->title('Milestone - List');

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
                    $this->github()->issue()->milestones()->create($project->organization(), $project->name(), ['title' => $releaseName]);
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

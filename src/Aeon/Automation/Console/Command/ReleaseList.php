<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
use Composer\Semver\Semver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ReleaseList extends AbstractCommand
{
    protected static $defaultName = 'release:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('List all project releases')
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('create-missing', 'cm', InputOption::VALUE_NONE, 'Create missing milestones for existing releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $milestones = $this->github()->issues()->milestones()->all($project->organization(), $project->name(), ['state' => 'all']);
        $releases = $this->github()->repository()->releases()->all($project->organization(), $project->name());

        $io->title('Release - List');

        $milestoneTitles = Semver::sort(\array_map(fn (array $milestoneData) => $milestoneData['title'], $milestones));
        $releaseNames = Semver::sort(\array_map(fn (array $releaseData) => $releaseData['name'], $releases));

        $io->note('Releases:');

        $missingMilestones = [];

        foreach ($releaseNames as $releaseName) {
            $releaseOutput = $releaseName;

            if (!\in_array($releaseName, $milestoneTitles, true)) {
                if ($input->getOption('create-missing') === true) {
                    $io->note('Creating milestone: ' . $releaseName);
                    $this->github()->issue()->milestones()->create($project->organization(), $project->name(), ['title' => $releaseName]);
                } else {
                    $releaseOutput .= ' - <fg=yellow>missing milestone</>';
                    $missingMilestones[] = $releaseName;
                }
            }

            $io->writeln($releaseOutput);
        }

        if (\count($missingMilestones) > 0) {
            $io->note('Create missing milestones automatically using --create-missing option');
        }

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
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
            ->addOption('create-missing', null, InputOption::VALUE_NONE, 'Create missing milestones for existing releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $milestones = $this->githubClient()->milestones($project)->semVerRsort();
        $releases = $this->githubClient()->releases($project)->semVerRsort();

        $io->title('Release - List');

        $io->note('Releases:');

        $missingMilestones = [];

        foreach ($releases->all() as $release) {
            $releaseOutput = $release->name();

            if (!$milestones->exists($release->name())) {
                if ($input->getOption('create-missing') === true) {
                    $io->note('Creating milestone: ' . $release->name());
                    $this->githubClient()->createMilestone($project, $release->name());
                } else {
                    $releaseOutput .= ' - <fg=yellow>missing milestone</>';
                    $missingMilestones[] = $release;
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

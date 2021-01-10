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

final class BranchList extends AbstractCommand
{
    protected static $defaultName = 'branch:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('List project branches')
            ->addArgument('project', InputArgument::REQUIRED, 'project name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Branch - List');

        $branches = $this->githubClient()->branches($project);
        $repository = $this->githubClient()->repository($project);

        foreach ($branches->all() as $branch) {
            if ($branch->isDefault($repository)) {
                $io->writeln('* <fg=green;options=bold>' . $branch->name() . '</>');
            } else {
                $io->writeln('  ' . $branch->name());
            }
        }

        return Command::SUCCESS;
    }
}

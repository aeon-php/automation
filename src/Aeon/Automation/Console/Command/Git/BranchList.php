<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command\Git;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Git\RepositoryLocation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class BranchList extends AbstractCommand
{
    protected static $defaultName = 'git:branch:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('List git repository branches, marked branch is active one.')
            ->addArgument('repository', InputArgument::OPTIONAL, 'local path to repository', '.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new RepositoryLocation($input->getArgument('repository'));

        $io->title('Branch - List');

        $branches = $this->git($project)->branches();
        $currentBranch = $this->git($project)->currentBranch();

        foreach ($branches->all() as $branch) {
            if ($branch->isEqual($currentBranch)) {
                $io->writeln('* <fg=green;options=bold>' . $branch->name() . '</>');
            } else {
                $io->writeln('  ' . $branch->name());
            }
        }

        return Command::SUCCESS;
    }
}

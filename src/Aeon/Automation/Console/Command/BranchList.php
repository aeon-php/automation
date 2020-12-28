<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\GitHub\Branches;
use Aeon\Automation\GitHub\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BranchList extends AbstractCommand
{
    protected static $defaultName = 'branch:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Branch - List');

        $branches = Branches::getAll($this->github(), $project);
        $repository = Repository::create($this->github(), $project);

        foreach ($branches->all() as $branch) {
            if ($branch->isDefault($repository)) {
                $io->note('Name: ' . $branch->name() . ' - default');
            } else {
                $io->note('Name: ' . $branch->name());
            }
        }

        return Command::SUCCESS;
    }
}

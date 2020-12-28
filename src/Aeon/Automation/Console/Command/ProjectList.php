<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ProjectList extends AbstractCommand
{
    protected static $defaultName = 'project:list';

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Project - List');

        foreach ($this->configuration()->projects() as $project) {
            $io->note('Project: ' . $project->name());
        }

        return Command::SUCCESS;
    }
}

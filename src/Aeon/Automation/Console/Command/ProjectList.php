<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ProjectList extends AbstractCommand
{
    protected static $defaultName = 'project:list';

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $io->title('Project - List');

        foreach ($this->configuration()->projects() as $project) {
            $io->writeln($project->name());
        }

        return Command::SUCCESS;
    }
}

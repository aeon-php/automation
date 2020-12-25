<?php

declare(strict_types=1);

namespace Aeon\Automation\Command;

use Aeon\Automation\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ProjectList  extends Command
{
    protected static $defaultName = 'project:list';

    private array $defaultConfigPaths;

    public function __construct(array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->defaultConfigPaths = $defaultConfigPaths;
    }

    protected function configure() : void
    {
        $this
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));

        foreach ($configuration->projects() as $project) {
            $io->note('Project: ' . $project->name());
        }

        return Command::SUCCESS;
    }
}

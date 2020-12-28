<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Configuration;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ProjectList extends Command
{
    protected static $defaultName = 'project:list';

    private array $defaultConfigPaths;

    private Client $github;

    public function __construct(Client $github, array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->defaultConfigPaths = $defaultConfigPaths;
        $this->github = $github;
    }

    protected function configure() : void
    {
        $this
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));

        if ($configuration->githubAccessToken()) {
            $this->github->authenticate($configuration->githubAccessToken(), null, Client::AUTH_ACCESS_TOKEN);
        }

        $io->title('Project - List');

        foreach ($configuration->projects() as $project) {
            $io->note('Project: ' . $project->name());
        }

        return Command::SUCCESS;
    }
}

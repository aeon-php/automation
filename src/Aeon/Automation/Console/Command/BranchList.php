<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Configuration;
use Aeon\Automation\GitHub\Branches;
use Aeon\Automation\GitHub\Repository;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BranchList extends Command
{
    protected static $defaultName = 'branch:list';

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
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));

        if ($configuration->githubAccessToken()) {
            $this->github->authenticate($configuration->githubAccessToken(), null, Client::AUTH_ACCESS_TOKEN);
        }

        $project = $configuration->project($input->getArgument('project'));

        $io->title('Branch - List');

        $branches = Branches::getAll($this->github, $project);
        $repository = Repository::create($this->github, $project);

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

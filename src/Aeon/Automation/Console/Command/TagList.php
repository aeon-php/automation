<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Configuration;
use Aeon\Automation\GitHub\Tags;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TagList extends Command
{
    protected static $defaultName = 'tag:list';

    private array $defaultConfigPaths;

    private Client $github;

    public function __construct(Client $client, array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->defaultConfigPaths = $defaultConfigPaths;
        $this->github = $client;
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

        $io->title('Tag - List');

        $tags = Tags::getAll($this->github, $project)->semVerRsort();

        foreach ($tags->all() as $tag) {
            $io->note($tag->name() . ' - ' . $tag->commit($this->github, $project)->date()->day()->toString());
        }

        return Command::SUCCESS;
    }
}

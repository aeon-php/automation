<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Configuration;
use Github\Client;
use Github\HttpClient\Builder;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Message\Formatter\FullHttpMessageFormatter;
use Http\Message\Formatter\SimpleFormatter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * @var string[];
     */
    protected array $defaultConfigPaths = [];

    private ?Configuration $configuration;

    private ?Client $github;

    private ?CacheItemPoolInterface $cache;

    public function __construct(array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->defaultConfigPaths = $defaultConfigPaths;
        $this->configuration = null;
        $this->github = null;
        $this->cache = null;
    }

    public function github() : Client
    {
        if ($this->github === null) {
            throw new \RuntimeException("Github client not initialized. Github client is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->github;
    }

    public function setGithub(Client $client) : void
    {
        $this->github = $client;
    }

    public function configuration() : Configuration
    {
        if ($this->configuration === null) {
            throw new \RuntimeException("Configuration is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->configuration;
    }

    public function cache() : CacheItemPoolInterface
    {
        if ($this->cache === null) {
            throw new \RuntimeException("Cache is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->cache;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) : void
    {
        $this->configuration = new Configuration($this->defaultConfigPaths, $input->getOption('configuration'));

        $this->initializeGithub($output, $input);
    }

    /**
     * @param OutputInterface $output
     * @param InputInterface $input
     */
    private function initializeGithub(OutputInterface $output, InputInterface $input) : void
    {
        if ($this->github !== null) {
            return;
        }

        $verbosityLevelMap = [
            LogLevel::INFO => OutputInterface::VERBOSITY_VERBOSE,
        ];

        $formatLevelMap = [
            LogLevel::ERROR => ConsoleLogger::ERROR,
            LogLevel::CRITICAL => ConsoleLogger::ERROR,
            LogLevel::INFO => ConsoleLogger::INFO,
        ];

        $logger = new ConsoleLogger($output, $verbosityLevelMap, $formatLevelMap);

        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $formatter = new SimpleFormatter();

                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $formatter = new FullHttpMessageFormatter(10000);

                break;

            default:
                $formatter = new SimpleFormatter();
        }

        $builder = new Builder();

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_VERBOSE) {
            $builder->addPlugin(new LoggerPlugin($logger, $formatter));
        }

        $client = new Client($builder);
        $client->addCache($cache = new FilesystemAdapter('aeon-automation'));

        $this->cache = $cache;
        $this->github = $client;

        if ($input->getOption('github-token')) {
            $this->github()->authenticate($input->getOption('github-token'), null, Client::AUTH_ACCESS_TOKEN);
        } elseif (\getenv('AEON_AUTOMATION_GH_TOKEN')) {
            $this->github()->authenticate(\getenv('AEON_AUTOMATION_GH_TOKEN'), null, Client::AUTH_ACCESS_TOKEN);
        }
    }
}

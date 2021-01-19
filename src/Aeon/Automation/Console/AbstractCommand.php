<?php

declare(strict_types=1);

namespace Aeon\Automation\Console;

use Aeon\Automation\Configuration;
use Aeon\Automation\GitHub\GitHubClient;
use Aeon\Calendar\Gregorian\Calendar;
use Aeon\Calendar\Gregorian\GregorianCalendar;
use Github\Client;
use Github\HttpClient\Builder;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Message\Formatter\FullHttpMessageFormatter;
use Http\Message\Formatter\SimpleFormatter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    private string $rootDir;

    /**
     * @var string[];
     */
    private array $defaultConfigPaths;

    private ?Configuration $configuration;

    private ?Client $github;

    private ?CacheItemPoolInterface $httpCache;

    private ?CacheItemPoolInterface $githubCache;

    private ?LoggerInterface $logger;

    private ?Calendar $calendar;

    public function __construct(string $rootDir, array $defaultConfigPaths = [])
    {
        parent::__construct();

        $this->rootDir = $rootDir;
        $this->defaultConfigPaths = $defaultConfigPaths;
        $this->configuration = null;
        $this->github = null;
        $this->httpCache = null;
        $this->githubCache = null;
        $this->calendar = null;
        $this->logger = null;
    }

    public function githubClient() : GitHubClient
    {
        return new GitHubClient($this->github(), $this->githubCache());
    }

    public function setGithub(Client $client) : void
    {
        $this->github = $client;
    }

    public function setGitHubCache(CacheItemPoolInterface $githubCache) : void
    {
        $this->githubCache = $githubCache;
    }

    public function setHttpCache(CacheItemPoolInterface $httpCache) : void
    {
        $this->httpCache = $httpCache;
    }

    public function calendar() : Calendar
    {
        if ($this->calendar === null) {
            throw new \RuntimeException("Calendar is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->calendar;
    }

    public function setCalendar(Calendar $calendar) : void
    {
        $this->calendar = $calendar;
    }

    public function configuration() : Configuration
    {
        if ($this->configuration === null) {
            throw new \RuntimeException("Configuration is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->configuration;
    }

    public function httpCache() : CacheItemPoolInterface
    {
        if ($this->httpCache === null) {
            throw new \RuntimeException("Cache is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->httpCache;
    }

    public function githubCache() : CacheItemPoolInterface
    {
        if ($this->githubCache === null) {
            throw new \RuntimeException("Cache is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->githubCache;
    }

    protected function interact(InputInterface $input, OutputInterface $output) : void
    {
        if ($input->hasArgument('project') && $input->getArgument('project') === null && $this->configuration()->project()) {
            $input->setArgument('project', $this->configuration()->project()->fullName());
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output) : void
    {
        $verbosityLevelMap = [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
            LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
        ];

        $formatLevelMap = [
            LogLevel::ERROR => ConsoleLogger::ERROR,
            LogLevel::CRITICAL => ConsoleLogger::ERROR,
            LogLevel::INFO => ConsoleLogger::INFO,
        ];

        $this->logger = new ConsoleLogger($output, $verbosityLevelMap, $formatLevelMap);

        $this->configuration = new Configuration($this->logger, $this->rootDir, $this->defaultConfigPaths, $input->getOption('configuration'));
        $cachePath = $input->getOption('cache-path');

        if (\getenv('AEON_AUTOMATION_CACHE_DIR')) {
            $cachePath = \getenv('AEON_AUTOMATION_CACHE_DIR');
        }

        $this->logger->info('Cache directory: ' . $cachePath);

        $this->httpCache = $this->httpCache === null ? new FilesystemAdapter('http-cache', 0, $cachePath . \DIRECTORY_SEPARATOR . 'automation-cache') : $this->httpCache;
        $this->httpCache->setLogger($this->logger);

        $this->githubCache = $this->githubCache === null ? new FilesystemAdapter('github-cache', 0, $cachePath . \DIRECTORY_SEPARATOR . 'automation-cache') : $this->githubCache;
        $this->githubCache->setLogger($this->logger);

        $this->initializeCalendar();
        $this->initializeGithub($this->httpCache, $this->logger, $output, $input);
    }

    private function initializeGithub(CacheItemPoolInterface $cache, LoggerInterface $logger, OutputInterface $output, InputInterface $input) : void
    {
        if ($this->github !== null) {
            return;
        }

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

        $builder->addPlugin(new HeaderDefaultsPlugin(['User-Agent' => 'aeon-php/automation (https://github.com/aeon-php/automation)']));

        $githubEnterpriseUrl = null;

        if ($input->getOption('github-enterprise-url')) {
            $githubEnterpriseUrl = $input->getOption('github-enterprise-url');
        } elseif (\getenv('AEON_AUTOMATION_GH_ENTERPRISE_URL')) {
            $githubEnterpriseUrl = \getenv('AEON_AUTOMATION_GH_ENTERPRISE_URL');
        }

        $client = new Client($builder, null, $githubEnterpriseUrl);
        $client->addCache($cache);

        $this->github = $client;

        if ($input->getOption('github-token')) {
            $this->github()->authenticate($input->getOption('github-token'), null, Client::AUTH_ACCESS_TOKEN);
        } elseif (\getenv('AEON_AUTOMATION_GH_TOKEN')) {
            $this->github()->authenticate(\getenv('AEON_AUTOMATION_GH_TOKEN'), null, Client::AUTH_ACCESS_TOKEN);
        }
    }

    private function initializeCalendar() : void
    {
        if ($this->calendar !== null) {
            return;
        }

        $this->calendar = GregorianCalendar::UTC();
    }

    private function github() : Client
    {
        if ($this->github === null) {
            throw new \RuntimeException("Github client not initialized. Github client is only accessible in Command::execute() method because it's initialized in Command::initialize()");
        }

        return $this->github;
    }
}

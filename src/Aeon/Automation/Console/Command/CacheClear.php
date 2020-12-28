<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CacheClear extends Command
{
    protected static $defaultName = 'cache:clear';

    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    protected function configure() : void
    {
        $this->setDescription('Clears cache used to cache HTTP responses from GitHub');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cache - Clear');

        $this->cache->clear();

        $io->success('Cache clear');

        return Command::SUCCESS;
    }
}

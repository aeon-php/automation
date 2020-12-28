<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CacheClear extends AbstractCommand
{
    protected static $defaultName = 'cache:clear';

    protected function configure() : void
    {
        parent::configure();

        $this->setDescription('Clears cache used to cache HTTP responses from GitHub');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $io->title('Cache - Clear');

        $this->cache()->clear();

        $io->success('Cache clear');

        return Command::SUCCESS;
    }
}

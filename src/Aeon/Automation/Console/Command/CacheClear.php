<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CacheClear extends AbstractCommand
{
    protected static $defaultName = 'cache:clear';

    protected function configure() : void
    {
        parent::configure();

        $this->setDescription('Clears all or specific caches.')
            ->setHelp(
                <<<'HELP'
<fg=yellow>Available Caches</>
 - <fg=yellow>github</> used to cache github objects without expiration date
 - <fg=yellow>http</> used to cache HTTP responses according to their headers
HELP
            )
            ->addOption('cache-name', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Caches to clear, available: <fg=yellow>http, github</>', ['http', 'github']);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $io->title('Cache - Clear');

        if (\in_array('http', $input->getOption('cache-name'), true)) {
            $io->note('Clearing HTTP cache');
            $this->httpCache()->clear();
        }

        if (\in_array('github', $input->getOption('cache-name'), true)) {
            $io->note('Clearing GitHub cache');
            $this->githubCache()->clear();
        }

        $io->success('Cache clear');

        return Command::SUCCESS;
    }
}

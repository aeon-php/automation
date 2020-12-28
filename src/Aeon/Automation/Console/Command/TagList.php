<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Tags;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TagList extends AbstractCommand
{
    protected static $defaultName = 'tag:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Display all tags following SemVer convention sorted from the latest to oldest')
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('with-date', 'wd', InputOption::VALUE_NONE, 'display date when tag was committed');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Tag - List');

        $tags = Tags::getAll($this->github(), $project)->semVerRsort();

        foreach ($tags->all() as $tag) {
            if ($input->getOption('with-date')) {
                $io->writeln($tag->name() . ' - ' . $tag->commit($this->github(), $project)->date()->day()->toString());
            } else {
                $io->writeln($tag->name());
            }
        }

        return Command::SUCCESS;
    }
}

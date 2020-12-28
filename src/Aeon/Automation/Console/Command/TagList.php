<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\GitHub\Tags;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TagList extends AbstractCommand
{
    protected static $defaultName = 'tag:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->addArgument('project', InputArgument::REQUIRED, 'project name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Tag - List');

        $tags = Tags::getAll($this->github(), $project)->semVerRsort();

        foreach ($tags->all() as $tag) {
            $io->note($tag->name() . ' - ' . $tag->commit($this->github(), $project)->date()->day()->toString());
        }

        return Command::SUCCESS;
    }
}

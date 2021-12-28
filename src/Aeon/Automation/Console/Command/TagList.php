<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Project;
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
            ->addOption('with-date', null, InputOption::VALUE_NONE, 'display date when tag was committed')
            ->addOption('with-commit', null, InputOption::VALUE_NONE, 'display commit SHA of tag')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of tags to get');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Tag - List');

        $tags = $this->githubClient($project)->tags()->rsort();

        if ($input->getOption('limit')) {
            $tags = $tags->limit((int) $input->getOption('limit'));
        }

        foreach ($tags->all() as $tag) {
            $tagOutput = $tag->name();
            $commit = null;

            if ($input->getOption('with-date')) {
                $commit = $this->githubClient($project)->tagCommit($tag);
                $tagOutput .= ' - ' . $commit->date()->day()->toString();
            }

            if ($input->getOption('with-commit')) {
                if ($commit === null) {
                    $commit = $this->githubClient($project)->tagCommit($tag);
                }

                $tagOutput .= ' - <fg=yellow>' . $commit->sha() . '</>';
            }

            $io->writeln($tagOutput);
        }

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\ChangeLog;
use Aeon\Automation\Changes\ChangesParser\ConventionalCommitParser;
use Aeon\Automation\Changes\ChangesParser\DefaultParser;
use Aeon\Automation\Changes\ChangesParser\HTMLChangesParser;
use Aeon\Automation\Changes\ChangesParser\PrefixParser;
use Aeon\Automation\Changes\ChangesParser\PrioritizedParser;
use Aeon\Automation\ChangesSource;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\GitHub\Commits;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Aeon\Automation\GitHub\Tags;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangeLogGet extends AbstractCommand
{
    private const FORMATTERS = [
        'markdown' => ChangeLog\MarkdownFormatter::class,
    ];

    protected static $defaultName = 'change-log:get';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Get project changelog from commits and pull requests')
            ->addArgument('project', InputArgument::REQUIRED, 'project name, for example aeon-php/calendar')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Get the the branch used instead of tag-start option when it\'s not provided. If empty, default repository branch is taken.')
            ->addOption('tag-start', 'ts', InputOption::VALUE_REQUIRED, 'Optional tag from which changelog generation starts. When not provided branch is used instead.')
            ->addOption('tag-end', 'te', InputOption::VALUE_REQUIRED, 'Optional tag until which changelog is generated. When not provided, latest tag is taken')
            ->addOption('commit-end', 'ce', InputOption::VALUE_REQUIRED, 'Optional commit sha until which changelog is generated . When not provided, latest tag is taken')
            ->addOption('only-commits', 'oc', InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', 'opr', InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('changed-after', 'cb', InputOption::VALUE_REQUIRED, 'Ignore all changes after given date, relative date formats like "-1 day" are also supported')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', \array_keys(self::FORMATTERS)) . '"</>', 'markdown');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Change Log - Get');

        $tags = Tags::getAll($this->github(), $project);
        $repository = Repository::create($this->github(), $project);

        $branchName = 'heads/' . ($input->getOption('branch') !== null ? $input->getOption('branch') : $repository->defaultBranch());
        $fromReferenceName = $input->getOption('tag-start') !== null ? ('tags/' . $input->getOption('tag-start')) : $branchName;
        $untilReferenceName = $input->getOption('tag-end') !== null
            ? 'tags/' . $input->getOption('tag-end')
            : ($input->getOption('tag-start') === null ? ($tags->count() ? 'tags/' . $tags->semVerRsort()->first()->name() : '') : '');

        $release = $input->getOption('tag-start') ? $input->getOption('tag-start') : 'Unreleased';

        $io->note('Format: ' . $input->getOption('format'));
        $io->note('Project: ' . $project->fullName());
        $io->note('From Reference: ' . $fromReferenceName);
        $io->note('Until Reference: ' . $untilReferenceName);
        $io->note('Until Commit: ' . $input->getOption('commit-end'));

        try {
            $untilCommit = $untilReferenceName !== ''
                ? Reference::commitFromString($this->github(), $project, $untilReferenceName)->commit($this->github(), $project)
                : null;
        } catch (RuntimeException $e) {
            $io->error('Reference "tags/' . $input->getOption('tag-end') . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        if ($input->getOption('commit-end')) {
            try {
                $untilCommit = Commit::fromSHA($this->github(), $project, $input->getOption('commit-end'));
            } catch (RuntimeException $e) {
                $io->error('Commit with SHA ' . $input->getOption('commit-end') . '" does not exists: ' . $e->getMessage());

                return Command::FAILURE;
            }
        }

        try {
            Reference::commitFromString($this->github(), $project, $branchName);
        } catch (RuntimeException $e) {
            $io->error('Branch "heads/' . $input->getArgument('branch') . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        try {
            $fromCommit = Reference::commitFromString($this->github(), $project, $fromReferenceName)->commit($this->github(), $project);
        } catch (RuntimeException $e) {
            $io->error("Reference \"{$fromReferenceName}\" does not exists: " . $e->getMessage());

            return Command::FAILURE;
        }

        $io->note("Fetching all commits between \"{$fromReferenceName}\" and \"{$untilReferenceName}\"");
        $commits = Commits::allFrom($this->github(), $project, $fromCommit, $untilCommit);
        $io->note('Total commits: ' . $commits->count());

        $io->progressStart($commits->count());

        $changeLog = new ChangeLog($release, $fromCommit->date()->day());

        $onlyCommits = $input->getOption('only-commits');
        $onlyPullRequests = $input->getOption('only-pull-requests');
        $changeAfter = $input->getOption('changed-after') ? DateTime::fromString($input->getOption('changed-after')) : null;

        if ($onlyCommits === true && $onlyPullRequests === true) {
            $io->error('--only-commits can\'t be used together with --only-pull-requests');

            return Command::FAILURE;
        }

        $changesParser = new PrioritizedParser(
            new HTMLChangesParser(),
            new ConventionalCommitParser(),
            new PrefixParser(),
            new DefaultParser()
        );

        foreach ($commits->all() as $commit) {
            $source = null;

            if ($changeAfter !== null) {
                if ($commit->date()->isBefore($changeAfter)) {
                    continue;
                }
            }

            if ($onlyCommits) {
                $source = $commit;
            }

            if ($onlyPullRequests) {
                $pullRequests = $commit->pullRequests($this->github(), $project);

                if (!$pullRequests->count()) {
                    continue;
                }

                $source = $pullRequests->first();
            }

            if (!$onlyPullRequests && !$onlyCommits) {
                $pullRequests = $commit->pullRequests($this->github(), $project);

                $source = $pullRequests->count() ? $pullRequests->first() : $commit;
            }

            if ($source instanceof ChangesSource) {
                $changeLog->add($changesParser->parse($source));
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->note('All commits analyzed, generating changelog: ');

        $formatterClass = self::FORMATTERS[\trim(\strtolower($input->getOption('format')))];
        $formatter = new $formatterClass();

        $io->write($formatter->format($changeLog));

        return Command::SUCCESS;
    }
}

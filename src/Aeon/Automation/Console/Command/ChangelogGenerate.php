<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\ChangeLog\MarkdownFormatter;
use Aeon\Automation\Changes\ChangesParser\ConventionalCommitParser;
use Aeon\Automation\Changes\ChangesParser\DefaultParser;
use Aeon\Automation\Changes\ChangesParser\HTMLChangesParser;
use Aeon\Automation\Changes\ChangesParser\PrefixParser;
use Aeon\Automation\Changes\ChangesParser\PrioritizedParser;
use Aeon\Automation\ChangesSource;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Branch;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\GitHub\Commits;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Aeon\Automation\GitHub\Tags;
use Aeon\Automation\Release;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ChangelogGenerate extends AbstractCommand
{
    protected static $defaultName = 'changelog:generate';

    private string $rootDir;

    public function __construct(string $rootDir, array $defaultConfigPaths = [])
    {
        parent::__construct($defaultConfigPaths);
        $this->rootDir = $rootDir;
    }

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('Generate change log for a release.')
            ->setHelp('When no parameters are provided, this command will generate UNRELEASED change log.')
            ->addArgument('project', InputArgument::REQUIRED, 'project name, for example aeon-php/calendar')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Get the the branch used instead of tag-start option when it\'s not provided. If empty, default repository branch is taken.')
            ->addOption('commit-start', 'cs', InputOption::VALUE_REQUIRED, 'Optional commit sha from which changelog is generated . When not provided, default branch latest commit is taken')
            ->addOption('commit-end', 'ce', InputOption::VALUE_REQUIRED, 'Optional commit sha until which changelog is generated . When not provided, latest tag is taken')
            ->addOption('changed-after', 'ca', InputOption::VALUE_REQUIRED, 'Ignore all changes after given date, relative date formats like "-1 day" are also supported')
            ->addOption('changed-before', 'cb', InputOption::VALUE_REQUIRED, 'Ignore all changes before given date, relative date formats like "-1 day" are also supported')
            ->addOption('tag', 't', InputOption::VALUE_REQUIRED, 'List only changes from given release')
            ->addOption('only-commits', 'oc', InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', 'opr', InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown']) . '"</>', 'markdown');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Changelog - Generate');

        $repository = Repository::create($this->github(), $project);

        $branchName = ($input->getOption('branch') !== null ? $input->getOption('branch') : $repository->defaultBranch());
        $commitStartSHA = $input->getOption('commit-start');
        $commitEndSHA = $input->getOption('commit-end');
        $tag = $input->getOption('tag');
        $onlyCommits = $input->getOption('only-commits');
        $onlyPullRequests = $input->getOption('only-pull-requests');
        $changeAfter = $input->getOption('changed-after') ? DateTime::fromString($input->getOption('changed-after')) : null;
        $changeBefore = $input->getOption('changed-before') ? DateTime::fromString($input->getOption('changed-before')) : null;

        if ($onlyCommits === true && $onlyPullRequests === true) {
            $io->error('--only-commits can\'t be used together with --only-pull-requests');

            return Command::FAILURE;
        }

        /** @var null|Commit $commitStart */
        $commitStart = null;
        /** @var null|Commit $commitEnd */
        $commitEnd = null;

        /** @var null|Tags $tags */
        $tags = null;

        $releaseName = $input->getOption('tag') ? $input->getOption('tag') : 'Unreleased';

        switch (\trim(\strtolower($input->getOption('format')))) {
            case 'markdown' :
                $formatter = new MarkdownFormatter($this->rootDir);

                break;

            default:
                $io->error('Invalid format: ' . $input->getOption('format'));

                return Command::FAILURE;
        }

        try {
            $branch = Branch::byName($this->github(), $project, $branchName);
        } catch (RuntimeException $e) {
            $io->error('Branch "' . $input->getArgument('branch') . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        if ($tag !== null) {
            try {
                $commitStart = Reference::tag($this->github(), $project, $tag)
                    ->commit($this->github(), $project);
            } catch (RuntimeException $e) {
                $io->error("Tag \"{$tag}\" does not exists: " . $e->getMessage());

                return Command::FAILURE;
            }

            $tags = Tags::getAll($this->github(), $project)->semVerRsort();

            if ($tags->count()) {
                $nextTag = $tags->next($tag);

                if ($nextTag !== null) {
                    $commitEnd = Reference::tag($this->github(), $project, $nextTag->name())
                        ->commit($this->github(), $project);
                }
            }
        }

        if ($commitStartSHA !== null) {
            try {
                $commitEnd = Commit::fromSHA($this->github(), $project, $commitStartSHA);
            } catch (RuntimeException $e) {
                $io->error("Commit \"{$commitStartSHA}\" does not exists: " . $e->getMessage());

                return Command::FAILURE;
            }
        }

        if ($commitEndSHA !== null) {
            try {
                $commitEnd = Commit::fromSHA($this->github(), $project, $commitEndSHA);
            } catch (RuntimeException $e) {
                $io->error("Commit \"{$commitEndSHA}\" does not exists: " . $e->getMessage());

                return Command::FAILURE;
            }
        }

        if ($commitStart === null && $commitEnd === null) {
            if ($tags === null) {
                $tags = Tags::getAll($this->github(), $project)->semVerRsort();
            }

            if ($tags->count()) {
                try {
                    $commitStart = Commit::fromSHA($this->github(), $project, $branch->sha());
                    $commitEnd = Reference::tag($this->github(), $project, $tags->first()->name())
                        ->commit($this->github(), $project);
                } catch (RuntimeException $e) {
                    // there are no previous tags, it should be safe to iterate through all commits
                }
            }
        }

        $io->note('Format: ' . $input->getOption('format'));
        $io->note('Project: ' . $project->fullName());
        $io->note('Branch: ' . $branchName);
        $io->note('Commit Start: ' . ($commitStart ? $commitStart->sha() : 'N/A'));
        $io->note('Commit End: ' . ($commitEnd ? $commitEnd->sha() : 'N/A'));
        $io->note('Changes After: ' . ($changeAfter ? $changeAfter->toISO8601() : 'N/A'));
        $io->note('Changes Before: ' . ($changeBefore ? $changeBefore->toISO8601() : 'N/A'));

        $changesParser = new PrioritizedParser(
            new HTMLChangesParser(),
            new ConventionalCommitParser(),
            new PrefixParser(),
            new DefaultParser()
        );

        $release = new Release($releaseName, $commitStart ? $commitStart->date()->day() : $this->calendar()->currentDay());

        if ($commitStart !== null && $commitEnd !== null) {
            $commits = Commits::betweenCommits($this->github(), $project, $commitStart, $commitEnd, $changeAfter, $changeBefore);
        } else {
            $commits = Commits::takeAll($this->github(), $project, $commitStart ? $commitStart->sha() : $branch->name(), $changeAfter, $changeBefore);
        }

        $io->note('Total commits: ' . $commits->count());

        $io->progressStart($commits->count());

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
                $release->add($changesParser->parse($source));
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->note('All commits analyzed, generating changelog: ');

        $io->write($formatter->format($release));

        return Command::SUCCESS;
    }
}

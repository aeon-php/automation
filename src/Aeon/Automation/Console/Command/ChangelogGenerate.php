<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\ChangeLog\TwigFormatter;
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
            ->addOption('commit-start', 'cs', InputOption::VALUE_REQUIRED, 'Optional commit sha from which changelog is generated . When not provided, default branch latest commit is taken')
            ->addOption('commit-end', 'ce', InputOption::VALUE_REQUIRED, 'Optional commit sha until which changelog is generated . When not provided, latest tag is taken')
            ->addOption('changed-after', 'ca', InputOption::VALUE_REQUIRED, 'Ignore all changes after given date, relative date formats like "-1 day" are also supported')
            ->addOption('changed-before', 'cb', InputOption::VALUE_REQUIRED, 'Ignore all changes before given date, relative date formats like "-1 day" are also supported')
            ->addOption('tag', 't', InputOption::VALUE_REQUIRED, 'List only changes from given release')
            ->addOption('only-commits', 'oc', InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', 'opr', InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', 'th', InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Changelog - Generate');

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

        $io->note('Release: ' . $releaseName);
        $io->note('Project: ' . $project->fullName());

        switch (\trim(\strtolower($input->getOption('format')))) {
            case 'markdown' :
            case 'html' :
                $formatter = new TwigFormatter(
                    $this->rootDir,
                    \trim(\strtolower($input->getOption('format'))),
                    \trim(\strtolower($input->getOption('theme')))
                );

                break;

            default:
                $io->error('Invalid format: ' . $input->getOption('format'));

                return Command::FAILURE;
        }

        $io->note('Format: ' . $input->getOption('format'));
        $io->note('Theme: ' . $input->getOption('theme'));

        if ($tag !== null) {
            try {
                $commitStart = Reference::tag($this->github(), $project, $tag)
                    ->commit($this->github(), $project);
                $io->note('Tag: ' . $tag);
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

                    $io->note('Tag End: ' . $nextTag->name());
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

            try {
                $branch = Branch::byName($this->github(), $project, $defaultBranch = Repository::create($this->github(), $project)->defaultBranch());
                $commitStart = Commit::fromSHA($this->github(), $project, $branch->sha());

                $io->note('Branch: ' . $defaultBranch);
            } catch (RuntimeException $e) {
                $io->error("Branch \"{$commitEndSHA}\" does not exists: " . $e->getMessage());

                return Command::FAILURE;
            }

            if ($tags->count()) {
                $io->note('Tag: ' . $tags->first()->name());

                try {
                    $commitEnd = Reference::tag($this->github(), $project, $tags->first()->name())
                        ->commit($this->github(), $project);
                } catch (RuntimeException $e) {
                    // there are no previous tags, it should be safe to iterate through all commits
                }
            }
        }

        if ($commitStart !== null) {
            $io->note('Commit Start: ' . $commitStart->sha());
        }

        if ($commitEnd !== null) {
            $io->note('Commit End: ' . $commitEnd->sha());
        }

        if ($changeAfter) {
            $io->note('Changes After: ' . $changeAfter->toISO8601());
        }

        if ($changeBefore) {
            $io->note('Changes Before: ' . $changeBefore->toISO8601());
        }

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
            $commits = Commits::takeAll($this->github(), $project, $commitStart->sha(), $changeAfter, $changeBefore);
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

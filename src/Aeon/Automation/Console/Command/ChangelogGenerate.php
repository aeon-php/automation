<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\ChangeLog\FormatterFactory;
use Aeon\Automation\ChangeLog\History;
use Aeon\Automation\ChangeLog\HistoryAnalyzer;
use Aeon\Automation\ChangeLog\ScopeDetector;
use Aeon\Automation\Changes\ChangesParser\ConventionalCommitParser;
use Aeon\Automation\Changes\ChangesParser\DefaultParser;
use Aeon\Automation\Changes\ChangesParser\HTMLChangesParser;
use Aeon\Automation\Changes\ChangesParser\PrefixParser;
use Aeon\Automation\Changes\ChangesParser\PrioritizedParser;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\Release;
use Aeon\Calendar\Gregorian\DateTime;
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
            ->addOption('tag-next', 'tn', InputOption::VALUE_REQUIRED, 'List only changes until given release')
            ->addOption('release-name', 'rn', InputOption::VALUE_REQUIRED, 'Name of the release when --tag option is not provided', 'Unreleased')
            ->addOption('only-commits', 'oc', InputOption::VALUE_NONE, 'Use only commits to generate changelog')
            ->addOption('only-pull-requests', 'opr', InputOption::VALUE_NONE, 'Use only pull requests to generate changelog')
            ->addOption('compare-reverse', 'cpr', InputOption::VALUE_NONE, 'When comparing commits, revers the order and compare start to end, instead end to start.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'How to format generated changelog, available formatters: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['markdown', 'html']) . '"</>', 'markdown')
            ->addOption('theme', 'th', InputOption::VALUE_REQUIRED, 'Theme of generated changelog: <fg=yellow>"' . \implode('"</>, <fg=yellow>"', ['keepachangelog', 'classic']) . '"</>', 'keepachangelog')
            ->addOption('skip-from', 'sf', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Skip changes from given author|authors');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Changelog - Generate');

        $commitStartSHA = $input->getOption('commit-start');
        $commitEndSHA = $input->getOption('commit-end');
        $tag = $input->getOption('tag');
        $tagNext = $input->getOption('tag-next');
        $onlyCommits = $input->getOption('only-commits');
        $compareReverse = $input->getOption('compare-reverse');
        $onlyPullRequests = $input->getOption('only-pull-requests');
        $changedAfter = $input->getOption('changed-after') ? DateTime::fromString($input->getOption('changed-after')) : null;
        $changedBefore = $input->getOption('changed-before') ? DateTime::fromString($input->getOption('changed-before')) : null;
        $skipAuthors = (array) $input->getOption('skip-from');

        $releaseName = $input->getOption('tag') ? $input->getOption('tag') : $input->getOption('release-name');

        $io->note('Release: ' . $releaseName);
        $io->note('Project: ' . $project->fullName());

        try {
            $formatter = (new FormatterFactory($this->rootDir))->create($input->getOption('format'), $input->getOption('theme'));
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->note('Format: ' . $input->getOption('format'));
        $io->note('Theme: ' . $input->getOption('theme'));

        try {
            $scopeDetector = (new ScopeDetector($this->github(), $project, $io));

            $scope = $scopeDetector->default(
                $scopeDetector->fromTags($tag, $tagNext)
                    ->override($scopeDetector->fromCommitSHA($commitStartSHA, $commitEndSHA))
            );

            if ($compareReverse && $scope->isFull()) {
                $io->note('Reversed Start with End commit');
                $scope = $scope->reverse();
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($scope->commitStart() !== null) {
            $io->note('Commit Start: ' . $scope->commitStart()->sha() . ($compareReverse ? ' - reversed' : ''));
        }

        if ($scope->commitEnd() !== null) {
            $io->note('Commit End: ' . $scope->commitEnd()->sha() . ($compareReverse ? ' - reversed' : ''));
        }

        if ($changedAfter) {
            $io->note('Changes After: ' . $changedAfter->toISO8601());
        }

        if ($changedBefore) {
            $io->note('Changes Before: ' . $changedBefore->toISO8601());
        }

        if (\count($skipAuthors)) {
            $io->note('Skip from: @' . \implode(', @', $skipAuthors));
        }

        try {
            $commits = (new History($this->github(), $project))->fetch($scope, $changedAfter, $changedBefore);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->note('Total commits: ' . $commits->count());

        $io->progressStart($commits->count());

        try {
            $changeSources = (new HistoryAnalyzer($this->github(), $project))->analyze(
                new HistoryAnalyzer\HistoryOptions($onlyCommits, $onlyPullRequests, $skipAuthors),
                $commits,
                function () use ($io) : void {
                    $io->progressAdvance();
                }
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->progressFinish();

        $release = new Release($releaseName, $scope->commitStart() ? $scope->commitStart()->date()->day() : $this->calendar()->currentDay());

        $changesParser = new PrioritizedParser(
            new HTMLChangesParser(),
            new ConventionalCommitParser(),
            new PrefixParser(),
            new DefaultParser()
        );

        foreach ($changeSources as $source) {
            $release->add($changesParser->parse($source));
        }

        $io->note('All commits analyzed, generating changelog: ');

        if (!$release->empty()) {
            $io->write($formatter->format($release));
        } else {
            $io->note('No changes');
        }

        return Command::SUCCESS;
    }
}

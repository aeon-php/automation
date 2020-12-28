<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\ChangeLog;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Commits;
use Aeon\Automation\GitHub\PullRequest;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Aeon\Automation\GitHub\Tags;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Exception\RuntimeException;
use Github\HttpClient\Message\ResponseMediator;
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

        try {
            $untilReference = $untilReferenceName !== ''
                ? Reference::commitFromString($this->github(), $project, $untilReferenceName)
                : null;
        } catch (RuntimeException $e) {
            $io->error('Reference "tags/' . $input->getOption('tag-end') . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        try {
            Reference::commitFromString($this->github(), $project, $branchName);
        } catch (RuntimeException $e) {
            $io->error('Branch "heads/' . $input->getArgument('branch') . '" does not exists: ' . $e->getMessage());

            return Command::FAILURE;
        }

        try {
            $fromReference = Reference::commitFromString($this->github(), $project, $fromReferenceName);
        } catch (RuntimeException $e) {
            $io->error("Reference \"{$fromReferenceName}\" does not exists: " . $e->getMessage());

            return Command::FAILURE;
        }

        $io->note("Fetching all commits between \"{$fromReferenceName}\" and \"{$untilReferenceName}\"");
        $commits = Commits::allFrom($this->github(), $project, $fromReference, $untilReference);
        $io->note('Total commits: ' . $commits->count());

        $io->progressStart($commits->count());

        $changeLog = new ChangeLog($release, $fromReference->commit($this->github(), $project)->date()->day());

        $onlyCommits = $input->getOption('only-commits');
        $onlyPullRequests = $input->getOption('only-pull-requests');
        $changeAfter = $input->getOption('changed-after') ? DateTime::fromString($input->getOption('changed-after')) : null;

        if ($onlyCommits === true && $onlyPullRequests === true) {
            $io->error('--only-commits can\'t be used together with --only-pull-requests');

            return Command::FAILURE;
        }

        foreach ($commits->all() as $commit) {
            if ($changeAfter !== null) {
                if ($commit->date()->isBefore($changeAfter)) {
                    continue;
                }
            }

            if ($onlyCommits) {
                $source = $commit;
            }

            if ($onlyPullRequests) {
                $pullRequestsData = ResponseMediator::getContent(
                    $this->github()->getHttpClient()->get(
                        '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/commits/' . \rawurlencode($commit->id()) . '/pulls',
                        ['Accept' => 'application/vnd.github.groot-preview+json']
                    )
                );

                if (!\count($pullRequestsData)) {
                    continue;
                }

                $source = new PullRequest($pullRequestsData[0]);
            }

            if (!$onlyPullRequests && !$onlyCommits) {
                $pullRequestsData = ResponseMediator::getContent(
                    $this->github()->getHttpClient()->get(
                        '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/commits/' . \rawurlencode($commit->id()) . '/pulls',
                        ['Accept' => 'application/vnd.github.groot-preview+json']
                    )
                );

                $source = \count($pullRequestsData) ? new PullRequest($pullRequestsData[0]) : $commit;
            }

            $changeLog->add($source->changes());

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

<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\ChangeLog\HistoryAnalyzer\HistoryOptions;
use Aeon\Automation\ChangesSource;
use Aeon\Automation\GitHub\Commits;
use Aeon\Automation\Project;
use Github\Client;

final class HistoryAnalyzer
{
    private Client $github;

    private Project $project;

    public function __construct(Client $client, Project $project)
    {
        $this->github = $client;
        $this->project = $project;
    }

    /**
     * @return ChangesSource[]
     */
    public function analyze(HistoryOptions $historyOptions, Commits $commits, ?callable $onProgress = null) : array
    {
        $sources = [];

        foreach ($commits->all() as $commit) {
            $source = null;

            if ($historyOptions->onlyCommits()) {
                $source = $commit;
            }

            if ($historyOptions->onlyPullRequests()) {
                $pullRequests = $commit->pullRequests($this->github, $this->project);

                if (!$pullRequests->count()) {
                    continue;
                }

                $source = $pullRequests->first();
            }

            if ($historyOptions->allSources()) {
                $pullRequests = $commit->pullRequests($this->github, $this->project);

                $source = $pullRequests->count() ? $pullRequests->first() : $commit;
            }

            if ($source instanceof ChangesSource) {
                if (!$source->isFrom(...$historyOptions->skippedAuthors())) {
                    $sources[] = $source;
                }
            }

            if (\is_callable($onProgress)) {
                $onProgress();
            }
        }

        return $sources;
    }
}

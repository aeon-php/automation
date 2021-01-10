<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Project;
use Github\Client;

final class HistoryTransformer
{
    private Client $github;

    private Project $project;

    private Options $releaseOptions;

    public function __construct(Client $client, Project $project, Options $releaseOptions)
    {
        $this->github = $client;
        $this->project = $project;
        $this->releaseOptions = $releaseOptions;
    }

    /**
     * @return ChangesSource[]
     */
    public function transform(History $history, ?callable $onProgress = null) : array
    {
        $sources = [];

        foreach ($history->commits()->all() as $commit) {
            $source = null;

            if ($this->releaseOptions->onlyCommits()) {
                $source = $commit;
            }

            if ($this->releaseOptions->onlyPullRequests()) {
                $pullRequests = $commit->pullRequests($this->github, $this->project);

                if (!$pullRequests->count()) {
                    continue;
                }

                $source = $pullRequests->first();
            }

            if ($this->releaseOptions->allSources()) {
                $pullRequests = $commit->pullRequests($this->github, $this->project);

                $source = $pullRequests->count() ? $pullRequests->first() : $commit;
            }

            if ($source instanceof ChangesSource) {
                if (!$source->isFrom(...$this->releaseOptions->skipAuthors())) {
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

<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\GitHub\GitHub;

final class HistoryTransformer
{
    private GitHub $github;

    private Options $releaseOptions;

    public function __construct(GitHub $client, Options $releaseOptions)
    {
        $this->github = $client;
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
                $source = ChangesSource::fromCommit($commit);
            }

            if ($this->releaseOptions->onlyPullRequests()) {
                $pullRequests = $this->github->commitPullRequests($commit);

                if (!$pullRequests->count()) {
                    continue;
                }

                $source = ChangesSource::fromPullRequest($pullRequests->first());
            }

            if ($this->releaseOptions->allSources()) {
                $pullRequests = $this->github->commitPullRequests($commit);

                $source = $pullRequests->count() ? ChangesSource::fromPullRequest($pullRequests->first()) : ChangesSource::fromCommit($commit);
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

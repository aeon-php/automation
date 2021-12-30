<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Git\Git;

final class HistoryTransformer
{
    private Git $git;

    private Options $releaseOptions;

    public function __construct(Git $git, Options $releaseOptions)
    {
        $this->git = $git;
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
                $pullRequests = $this->git->commitPullRequests($commit);

                if (!$pullRequests->count()) {
                    continue;
                }

                $source = ChangesSource::fromPullRequest($pullRequests->first());
            }

            if ($this->releaseOptions->allSources()) {
                $pullRequests = $this->git->commitPullRequests($commit);

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

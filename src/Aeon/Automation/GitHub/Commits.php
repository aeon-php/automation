<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Client;
use Github\ResultPager;

final class Commits
{
    /**
     * @var Commit[]
     */
    private array $commits;

    public function __construct(Commit ...$commits)
    {
        $this->commits = $commits;
    }

    public static function betweenCommits(Client $client, Project $project, Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : self
    {
        $commitsPaginator = new ResultPager($client);
        $commitsData = $commitsPaginator->fetch($client->repo()->commits(), 'compare', [$project->organization(), $project->name(), $untilCommit->sha(), $fromCommit->sha()]);

        $totalCommits = $commitsData['total_commits'];
        $commitsData = $commitsData['commits'];

        $remainingCommitsCount = $totalCommits - \count($commitsData);

        $commits = new self(
            ...\array_map(
                fn (array $commitData) : Commit => new Commit($commitData),
                \array_reverse($commitsData)
            )
        );

        // compare API has limit to return maximum 250 commits in the chronological order
        // in order to get more commits we need to start from the first one (it's the last on in the history)
        // and use commits list API (skipping first one to avoid duplicates) to get remaining commits.
        if ($remainingCommitsCount > 0) {
            $remainingCommits = self::takeAll($client, $project, \current($commitsData)['sha'], $changedAfter, $changedBefore, $remainingCommitsCount + 1);
            $commits = $commits->merge($remainingCommits->skip(1));
        }

        return $commits;
    }

    public static function takeAll(Client $client, Project $project, string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : self
    {
        $parameters = ['sha' => $sha];

        if ($changedAfter !== null) {
            $parameters['since'] = $changedAfter->toISO8601();
        }

        if ($changedBefore !== null) {
            $parameters['until'] = $changedBefore->toISO8601();
        }

        $commitsPaginator = new ResultPager($client);
        $commitsData = $commitsPaginator->fetch($client->repo()->commits(), 'all', [$project->organization(), $project->name(), $parameters]);

        $foundAll = false;

        $commits = [];
        $totalCommits = 0;

        while ($foundAll === false) {
            foreach ($commitsData as $commitData) {
                $commit = new Commit($commitData);

                if ($limit !== null && $totalCommits >= $limit) {
                    $foundAll = true;

                    break;
                }

                $commits[] = $commit;
                $totalCommits += 1;
            }

            if ($foundAll) {
                break;
            }

            if ($commitsPaginator->hasNext()) {
                $commitsData = $commitsPaginator->fetchNext();
            } else {
                break;
            }
        }

        return new self(...$commits);
    }

    public function merge(self $commits) : self
    {
        return new self(...\array_merge(
            $this->commits,
            $commits->commits
        ));
    }

    public function count() : int
    {
        return \count($this->commits);
    }

    /**
     * @return Commit[]
     */
    public function all() : array
    {
        return $this->commits;
    }

    public function skip(int $commits) : self
    {
        if ($commits >= $this->count()) {
            return new self();
        }

        return new self(...\array_slice($this->commits, $commits, $this->count() -1));
    }
}

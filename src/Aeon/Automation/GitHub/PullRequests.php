<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;
use Github\ResultPager;

final class PullRequests
{
    /**
     * @var PullRequest[]
     */
    private array $pullRequests;

    public function __construct(PullRequest ...$pullRequests)
    {
        $this->pullRequests = $pullRequests;
    }

    public static function allClosedFor(Client $client, Project $project, string $branch, int $limit) : self
    {
        return self::allFor($client, $project, $branch, 'closed', $limit);
    }

    public static function allOpenFor(Client $client, Project $project, string $branch, int $limit) : self
    {
        return self::allFor($client, $project, $branch, 'open', $limit);
    }

    public function onlyMerged() : self
    {
        $pulls = [];

        foreach ($this->pullRequests as $pullRequest) {
            if ($pullRequest->isMerged()) {
                $pulls[] = $pullRequest;
            }
        }

        return new self(...$pulls);
    }

    public function count() : int
    {
        return \count($this->pullRequests);
    }

    public function first() : ?PullRequest
    {
        if (!$this->count()) {
            return null;
        }

        return \current($this->pullRequests);
    }

    /**
     * @return PullRequest[]
     */
    public function all() : array
    {
        return $this->pullRequests;
    }

    private static function allFor(Client $client, Project $project, string $branch, string $state, int $limit) : self
    {
        $pullsPaginator = new ResultPager($client);
        $pullsData = $pullsPaginator->fetch(
            $client->pullRequests(),
            'all',
            [
                $project->organization(),
                $project->name(),
                [
                    'state' => $state,
                    'base' => $branch,
                ],
            ]
        );

        $pullRequests = [];

        while (true) {
            foreach ($pullsData as $pullData) {
                $pullRequests[] = new PullRequest($pullData);

                if (\count($pullRequests) >= $limit) {
                    break;
                }
            }

            if (\count($pullRequests) >= $limit) {
                break;
            }

            if ($pullsPaginator->hasNext()) {
                $pullsData = $pullsPaginator->fetchNext();
            } else {
                break;
            }
        }

        return new self(...$pullRequests);
    }
}

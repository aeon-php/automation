<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

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
}

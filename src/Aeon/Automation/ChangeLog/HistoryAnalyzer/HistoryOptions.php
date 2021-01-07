<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog\HistoryAnalyzer;

final class HistoryOptions
{
    private ?bool $onlyCommits;

    private ?bool $onlyPullRequests;

    /**
     * @var string[]
     */
    private array $skippedAuthors;

    public function __construct(bool $onlyCommits, bool $onlyPullRequests, array $skippedAuthors)
    {
        if ($onlyCommits === true && $onlyPullRequests === true) {
            throw new \InvalidArgumentException("--only-commits and --only-pull-requests can't be used together");
        }

        $this->onlyCommits = $onlyCommits;
        $this->onlyPullRequests = $onlyPullRequests;
        $this->skippedAuthors = $skippedAuthors;
    }

    public function onlyCommits() : bool
    {
        return $this->onlyCommits;
    }

    public function onlyPullRequests() : bool
    {
        return $this->onlyPullRequests;
    }

    public function allSources() : bool
    {
        return $this->onlyPullRequests === false && $this->onlyCommits === false;
    }

    /**
     * @return string[]
     */
    public function skippedAuthors() : array
    {
        return $this->skippedAuthors;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Calendar\Gregorian\DateTime;

final class Options
{
    private string $releaseName;

    private ?string $branch;

    private ?string $commitStartSHA;

    private ?string $commitEndSHA;

    private ?string $tagStart;

    private ?string $tagNext;

    private bool $tagOnlyStable;

    private bool $onlyCommits;

    private bool $onlyPullRequests;

    private bool $compareReverse;

    private ?DateTime $changedAfter;

    private ?DateTime $changedBefore;

    private array $skipAuthors;

    public function __construct(
        string $releaseName,
        ?string $branch = null,
        ?string $commitStartSHA = null,
        ?string $commitEndSHA = null,
        ?string $tagStart = null,
        ?string $tagNext = null,
        bool $onlyCommits = false,
        bool $onlyPullRequests = false,
        bool $compareReverse = false,
        ?DateTime $changedAfter = null,
        ?DateTime $changedBefore = null,
        array $skipAuthors = []
    ) {
        if ($onlyCommits === true && $onlyPullRequests === true) {
            throw new \InvalidArgumentException("--only-commits and --only-pull-requests can't be used together");
        }

        if ($changedBefore && $changedAfter) {
            if ($changedBefore->isAfter($changedAfter)) {
                throw new \InvalidArgumentException("--changed-before can't be a date after --changed-after");
            }
        }

        if ($commitStartSHA !== null && $branch !== null) {
            throw new \InvalidArgumentException("--commit-start and --branch can't be used together");
        }

        if ($commitEndSHA !== null && $branch !== null) {
            throw new \InvalidArgumentException("--commit-end and --branch can't be used together");
        }

        $this->releaseName = $releaseName;
        $this->commitStartSHA = $commitStartSHA;
        $this->commitEndSHA = $commitEndSHA;
        $this->tagStart = $tagStart;
        $this->tagNext = $tagNext;
        $this->tagOnlyStable = false;
        $this->onlyCommits = $onlyCommits;
        $this->onlyPullRequests = $onlyPullRequests;
        $this->compareReverse = $compareReverse;
        $this->changedAfter = $changedAfter;
        $this->changedBefore = $changedBefore;
        $this->skipAuthors = $skipAuthors;
        $this->branch = $branch;
    }

    public function releaseName() : string
    {
        return $this->releaseName;
    }

    public function branch() : ?string
    {
        return $this->branch;
    }

    public function commitStartSHA() : ?string
    {
        return $this->commitStartSHA;
    }

    public function commitEndSHA() : ?string
    {
        return $this->commitEndSHA;
    }

    public function tagStart() : ?string
    {
        return $this->tagStart;
    }

    public function tagEnd() : ?string
    {
        return $this->tagNext;
    }

    public function tagOnlyStable() : void
    {
        $this->tagOnlyStable = true;
    }

    public function isTagOnlyStable() : bool
    {
        return $this->tagOnlyStable;
    }

    public function onlyCommits() : bool
    {
        return $this->onlyCommits;
    }

    public function onlyPullRequests() : bool
    {
        return $this->onlyPullRequests;
    }

    public function compareReverse() : bool
    {
        return $this->compareReverse;
    }

    public function changedAfter() : ?DateTime
    {
        return $this->changedAfter;
    }

    public function changedBefore() : ?DateTime
    {
        return $this->changedBefore;
    }

    /**
     * @return string[]
     */
    public function skipAuthors() : array
    {
        return $this->skipAuthors;
    }

    public function allSources() : bool
    {
        return $this->onlyPullRequests === false && $this->onlyCommits === false;
    }
}

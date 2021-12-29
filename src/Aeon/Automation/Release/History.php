<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Git\Commits;
use Aeon\Automation\GitHub\GitHub;
use Aeon\Calendar\Gregorian\DateTime;

final class History
{
    private GitHub $github;

    private Scope $scope;

    private ?DateTime $changedAfter;

    private ?DateTime $changedBefore;

    private ?Commits $commits;

    public function __construct(GitHub $github, Scope $scope, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null)
    {
        $this->github = $github;
        $this->scope = $scope;
        $this->changedAfter = $changedAfter;
        $this->changedBefore = $changedBefore;
        $this->commits = null;
    }

    public function scope() : Scope
    {
        return $this->scope;
    }

    public function changedAfter() : ?DateTime
    {
        return $this->changedAfter;
    }

    public function changedBefore() : ?DateTime
    {
        return $this->changedBefore;
    }

    public function commits() : Commits
    {
        if ($this->commits instanceof Commits) {
            return $this->commits;
        }

        if ($this->scope->commitStart() !== null && $this->scope->commitEnd() !== null) {
            $this->commits = $this->github->commitsCompare($this->scope->commitStart(), $this->scope->commitEnd(), $this->changedAfter, $this->changedBefore)->reverse();
        } else {
            $this->commits = $this->github->commits($this->scope->commitStart()->sha(), $this->changedAfter, $this->changedBefore);
        }

        return $this->commits;
    }
}

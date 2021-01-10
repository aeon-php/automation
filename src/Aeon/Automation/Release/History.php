<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\GitHub\Commits;
use Aeon\Automation\GitHub\GitHub;
use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;

final class History
{
    private GitHub $github;

    private Project $project;

    private Scope $scope;

    private ?DateTime $changedAfter;

    private ?DateTime $changedBefore;

    private ?Commits $commits;

    public function __construct(GitHub $github, Project $project, Scope $scope, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null)
    {
        $this->github = $github;
        $this->project = $project;
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
            $this->commits = $this->github->commitsCompare($this->project, $this->scope->commitStart(), $this->scope->commitEnd(), $this->changedAfter, $this->changedBefore);
        } else {
            $this->commits = $this->github->commits($this->project, $this->scope->commitStart()->sha(), $this->changedAfter, $this->changedBefore);
        }

        return $this->commits;
    }
}

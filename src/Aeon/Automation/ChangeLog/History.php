<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\GitHub\Commits;
use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Client;

final class History
{
    private Client $github;

    private Project $project;

    public function __construct(Client $github, Project $project)
    {
        $this->github = $github;
        $this->project = $project;
    }

    public function fetch(Scope $scope, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits
    {
        if ($scope->commitStart() !== null && $scope->commitEnd() !== null) {
            return Commits::betweenCommits($this->github, $this->project, $scope->commitStart(), $scope->commitEnd(), $changedAfter, $changedBefore);
        }

        return Commits::takeAll($this->github, $this->project, $scope->commitStart()->sha(), $changedAfter, $changedBefore);
    }
}

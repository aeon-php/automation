<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\GitHub\Tags;
use Aeon\Automation\Project;
use Github\Client;

final class ScopeDetector
{
    private Client $github;

    private Project $project;

    private ?Tags $tags;

    public function __construct(Client $github, Project $project)
    {
        $this->github = $github;
        $this->project = $project;
        $this->tags = null;
    }

    public function default(Scope $scope) : Scope
    {
        if ($scope->isFull()) {
            return $scope;
        }

        if ($scope->commitStart() === null) {
            $scope = $scope->override(Scope::fromDefaultBranchHead($this->github, $this->project));
        }

        if ($scope->commitEnd() === null) {
            if ($this->tags()->count()) {
                $scope = $scope->override(Scope::fromTagEnd($this->tags()->first()->name(), $this->github, $this->project));
            }
        }

        return $scope;
    }

    public function fromTags(?string $tag, ?string $tagNext) : ?Scope
    {
        $scope = Scope::empty();

        if ($tag !== null) {
            $scope = $scope->override(Scope::fromTagStart($tag, $this->github, $this->project));

            if ($this->tags()->count() && $tagNext === null) {
                if ($this->tags()->next($tag) !== null) {
                    $scope = $scope->override(Scope::fromTagEnd($this->tags()->next($tag)->name(), $this->github, $this->project));
                }
            }
        }

        if ($tagNext !== null) {
            $scope = $scope->override(Scope::fromTagEnd($tagNext, $this->github, $this->project));
        }

        return $scope;
    }

    public function fromCommitSHA(?string $commitStartSHA, ?string $commitEndSHA) : ?Scope
    {
        $scope = Scope::empty();

        if ($commitStartSHA !== null) {
            $scope = $scope->override(Scope::fromCommitStart($commitStartSHA, $this->github, $this->project));
        }

        if ($commitEndSHA !== null) {
            $scope = $scope->override(Scope::fromCommitEnd($commitEndSHA, $this->github, $this->project));
        }

        return $scope;
    }

    private function tags() : Tags
    {
        if ($this->tags !== null) {
            return $this->tags;
        }

        $this->tags = Tags::getAll($this->github, $this->project)->semVerRsort();

        return $this->tags;
    }
}

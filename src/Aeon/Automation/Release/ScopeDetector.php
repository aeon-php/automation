<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\GitHub\GitHub;
use Aeon\Automation\GitHub\Tags;

final class ScopeDetector
{
    private GitHub $github;

    private ?Tags $tags;

    private bool $onlyStableTags;

    public function __construct(GitHub $github, bool $onlyStableTags)
    {
        $this->github = $github;
        $this->tags = null;
        $this->onlyStableTags = $onlyStableTags;
    }

    public function default(Scope $scope) : Scope
    {
        if ($scope->isFull()) {
            return $scope;
        }

        if ($scope->commitStart() === null) {
            $scope = $scope->override(Scope::fromDefaultBranchHead($this->github));
        }

        if ($scope->commitEnd() === null && $scope->tagStart() === null) {
            if ($this->tags()->count()) {
                $scope = $scope->override(Scope::fromTagEnd($this->tags()->first()->name(), $this->github));
            }
        }

        return $scope;
    }

    public function fromTags(?string $tag, ?string $tagNext) : ?Scope
    {
        $scope = Scope::empty();

        if ($tag !== null) {
            $scope = $scope->override(Scope::fromTagStart($tag, $this->github));

            if ($this->tags()->count() && $tagNext === null) {
                if ($this->tags()->next($tag) !== null) {
                    $scope = $scope->override(Scope::fromTagEnd($this->tags()->next($tag)->name(), $this->github));
                }
            }
        }

        if ($tagNext !== null) {
            $scope = $scope->override(Scope::fromTagEnd($tagNext, $this->github));
        }

        return $scope;
    }

    public function fromCommitSHA(?string $commitStartSHA, ?string $commitEndSHA) : ?Scope
    {
        $scope = Scope::empty();

        if ($commitStartSHA !== null) {
            $scope = $scope->override(Scope::fromCommitStart($commitStartSHA, $this->github));
        }

        if ($commitEndSHA !== null) {
            $scope = $scope->override(Scope::fromCommitEnd($commitEndSHA, $this->github));
        }

        return $scope;
    }

    private function tags() : Tags
    {
        if ($this->tags !== null) {
            return $this->tags;
        }

        $this->tags = $this->github->tags()->rsort();

        if ($this->onlyStableTags) {
            $this->tags = $this->tags->onlyStable();
        }

        return $this->tags;
    }
}

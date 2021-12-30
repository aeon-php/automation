<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Git\Git;
use Aeon\Automation\Git\Tags;

final class ScopeDetector
{
    private Git $git;

    private ?Tags $tags;

    private bool $onlyStableTags;

    public function __construct(Git $github, bool $onlyStableTags)
    {
        $this->git = $github;
        $this->tags = null;
        $this->onlyStableTags = $onlyStableTags;
    }

    public function default(Scope $scope, ?string $defaultBranch) : Scope
    {
        if ($scope->isFull()) {
            return $scope;
        }

        if ($scope->commitStart() === null) {
            $scope = $scope->override(
                $defaultBranch
                    ? Scope::fromBranchHead($this->git, $defaultBranch)
                    : Scope::fromCurrentBranchHead($this->git)
            );
        }

        if ($scope->commitEnd() === null && $scope->tagStart() === null) {
            if ($this->tags()->count()) {
                $scope = $scope->override(Scope::fromTagEnd($this->tags()->first()->name(), $this->git));
            }
        }

        return $scope;
    }

    public function fromTags(?string $tag, ?string $tagNext) : ?Scope
    {
        $scope = Scope::empty();

        if ($tag !== null) {
            $scope = $scope->override(Scope::fromTagStart($tag, $this->git));

            if ($this->tags()->count() && $tagNext === null) {
                if ($this->tags()->next($tag) !== null) {
                    $scope = $scope->override(Scope::fromTagEnd($this->tags()->next($tag)->name(), $this->git));
                }
            }
        }

        if ($tagNext !== null) {
            $scope = $scope->override(Scope::fromTagEnd($tagNext, $this->git));
        }

        return $scope;
    }

    public function fromCommitSHA(?string $commitStartSHA, ?string $commitEndSHA) : ?Scope
    {
        $scope = Scope::empty();

        if ($commitStartSHA !== null) {
            $scope = $scope->override(Scope::fromCommitStart($commitStartSHA, $this->git));
        }

        if ($commitEndSHA !== null) {
            $scope = $scope->override(Scope::fromCommitEnd($commitEndSHA, $this->git));
        }

        return $scope;
    }

    private function tags() : Tags
    {
        if ($this->tags !== null) {
            return $this->tags;
        }

        $this->tags = $this->git->tags()->rsort();

        if ($this->onlyStableTags) {
            $this->tags = $this->tags->onlyStable();
        }

        return $this->tags;
    }
}

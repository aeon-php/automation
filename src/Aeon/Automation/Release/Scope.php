<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\GitHub\Branch;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\GitHub\GitHub;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\Project;
use Github\Exception\RuntimeException;

final class Scope
{
    private ?Commit $commitStart;

    private ?Commit $commitEnd;

    private ?Branch $branch;

    private ?Reference $tagStart;

    private ?Reference $tagEnd;

    public function __construct(
        ?Commit $commitStart = null,
        ?Commit $commitEnd = null,
        ?Branch $branch = null,
        ?Reference $tagStart = null,
        ?Reference $tagEnd = null
    ) {
        $this->commitStart = $commitStart;
        $this->commitEnd = $commitEnd;
        $this->branch = $branch;
        $this->tagStart = $tagStart;
        $this->tagEnd = $tagEnd;
    }

    public static function empty() : self
    {
        return new self();
    }

    public static function fromDefaultBranchHead(GitHub $github, Project $project) : self
    {
        try {
            $branch = $github->branch($project, $defaultBranch = $github->repository($project)->defaultBranch());

            return new self(
                $github->commit($project, $branch->sha()),
                null,
                $branch
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Can't fetch SHA for default branch does not exists: " . $e->getMessage());
        }
    }

    public static function fromTagStart(string $name, GitHub $client, Project $project) : self
    {
        try {
            $tag = $client->referenceTag($project, $name);

            return new self(
                $client->referenceCommit($project, $tag),
                null,
                null,
                $tag
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Tag {$name} does not exists: " . $e->getMessage());
        }
    }

    public static function fromTagEnd(string $name, GitHub $client, Project $project) : self
    {
        try {
            $tag = $client->referenceTag($project, $name);

            return new self(
                null,
                $client->referenceCommit($project, $tag),
                null,
                null,
                $tag
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Tag {$name} does not exists: " . $e->getMessage());
        }
    }

    public static function fromCommitStart(string $SHA, GitHub $client, Project $project) : self
    {
        try {
            return new self($client->commit($project, $SHA));
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Commit \"{$SHA}\" does not exists: " . $e->getMessage());
        }
    }

    public static function fromCommitEnd(string $SHA, GitHub $client, Project $project) : self
    {
        try {
            return new self(null, $client->commit($project, $SHA));
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Commit \"{$SHA}\" does not exists: " . $e->getMessage());
        }
    }

    public function override(self $scope) : self
    {
        return new self(
            $scope->commitStart() ? $scope->commitStart() : $this->commitStart,
            $scope->commitEnd() ? $scope->commitEnd() : $this->commitEnd,
            $scope->branch() ? $scope->branch() : $this->branch,
            $scope->tagStart() ? $scope->tagStart() : $this->tagStart,
            $scope->tagEnd() ? $scope->tagEnd() : $this->tagEnd
        );
    }

    public function commitStart() : ?Commit
    {
        return $this->commitStart;
    }

    public function commitEnd() : ?Commit
    {
        return $this->commitEnd;
    }

    public function branch() : ?Branch
    {
        return $this->branch;
    }

    public function tagStart() : ?Reference
    {
        return $this->tagStart;
    }

    public function tagEnd() : ?Reference
    {
        return $this->tagEnd;
    }

    public function reverse() : self
    {
        return new self($this->commitEnd, $this->commitStart, $this->branch, $this->tagStart, $this->tagEnd);
    }

    public function isFull() : bool
    {
        return $this->commitStart !== null && $this->commitEnd !== null;
    }

    public function isEmpty() : bool
    {
        return $this->commitStart === null && $this->commitEnd === null;
    }
}

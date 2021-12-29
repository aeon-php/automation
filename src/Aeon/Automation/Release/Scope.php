<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Git\Branch;
use Aeon\Automation\Git\Commit;
use Aeon\Automation\Git\Reference;
use Aeon\Automation\GitHub\GitHub;
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

    public static function fromDefaultBranchHead(GitHub $github) : self
    {
        try {
            $branch = $github->branch($defaultBranch = $github->repository()->defaultBranch());

            return new self(
                $github->commit($branch->sha()),
                null,
                $branch
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Can't fetch SHA for default branch does not exists: " . $e->getMessage());
        }
    }

    public static function fromTagStart(string $name, GitHub $client) : self
    {
        try {
            $tag = $client->referenceTag($name);

            return new self(
                $client->referenceCommit($tag),
                null,
                null,
                $tag
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Tag {$name} does not exists: " . $e->getMessage());
        }
    }

    public static function fromTagEnd(string $name, GitHub $client) : self
    {
        try {
            $tag = $client->referenceTag($name);

            return new self(
                null,
                $client->referenceCommit($tag),
                null,
                null,
                $tag
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Tag {$name} does not exists: " . $e->getMessage());
        }
    }

    public static function fromCommitStart(string $SHA, GitHub $client) : self
    {
        try {
            return new self($client->commit($SHA));
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Commit \"{$SHA}\" does not exists: " . $e->getMessage());
        }
    }

    public static function fromCommitEnd(string $SHA, GitHub $client) : self
    {
        try {
            return new self(null, $client->commit($SHA));
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

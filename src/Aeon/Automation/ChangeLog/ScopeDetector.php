<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Branch;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\GitHub\Reference;
use Aeon\Automation\GitHub\Repository;
use Aeon\Automation\GitHub\Tags;
use Aeon\Automation\Project;
use Github\Client;
use Github\Exception\RuntimeException;

final class ScopeDetector
{
    private Client $github;

    private Project $project;

    private ?Tags $tags;

    private AeonStyle $io;

    public function __construct(Client $github, Project $project, AeonStyle $io)
    {
        $this->github = $github;
        $this->project = $project;
        $this->tags = null;
        $this->io = $io;
    }

    public function default(Scope $scope) : Scope
    {
        if ($scope->isFull()) {
            return $scope;
        }

        if ($scope->commitStart() === null) {
            try {
                $branch = Branch::byName($this->github, $this->project, $defaultBranch = Repository::fromProject($this->github, $this->project)->defaultBranch());
                $scope = $scope->override(
                    new Scope(
                        Commit::fromSHA($this->github, $this->project, $branch->sha()),
                        null
                    )
                );

                $this->io->note('Branch: ' . $defaultBranch);
            } catch (RuntimeException $e) {
                throw new \RuntimeException("Can't fetch SHA for default branch does not exists: " . $e->getMessage());
            }
        }

        if ($scope->commitEnd() === null) {
            if ($this->tags()->count()) {
                $this->io->note('Tag: ' . $this->tags()->first()->name());

                try {
                    $scope = $scope->override(
                        new Scope(
                            null,
                            Reference::tag($this->github, $this->project, $this->tags()->first()->name())
                                ->commit($this->github, $this->project)
                        )
                    );
                } catch (RuntimeException $e) {
                    // there are no previous tags, it should be safe to iterate through all commits
                }
            }
        }

        return $scope;
    }

    public function fromTags(?string $tag, ?string $tagNext) : ?Scope
    {
        $commitStart = null;
        $commitEnd = null;

        if ($tag !== null) {
            try {
                $commitStart = Reference::tag($this->github, $this->project, $tag)
                    ->commit($this->github, $this->project);

                $this->io->note('Tag: ' . $tag);
            } catch (RuntimeException $e) {
                throw new \RuntimeException("Tag \"{$tag}\" does not exists: " . $e->getMessage());
            }

            if ($this->tags()->count() && $tagNext === null) {
                if ($this->tags()->next($tag) !== null) {
                    $commitEnd = Reference::tag($this->github, $this->project, $this->tags()->next($tag)->name())
                        ->commit($this->github, $this->project);

                    $this->io->note('Tag End: ' . $this->tags()->next($tag)->name());
                }
            }
        }

        if ($tagNext !== null) {
            try {
                $commitEnd = Reference::tag($this->github, $this->project, $tagNext)
                    ->commit($this->github, $this->project);
                $this->io->note('Tag End: ' . $tagNext);
            } catch (RuntimeException $e) {
                throw new \RuntimeException("Tag \"{$tag}\" does not exists: " . $e->getMessage());
            }
        }

        return new Scope($commitStart, $commitEnd);
    }

    public function fromCommitSHA(?string $commitStartSHA, ?string $commitEndSHA) : ?Scope
    {
        $commitStart = null;
        $commitEnd = null;

        if ($commitStartSHA !== null) {
            try {
                $commitStart = Commit::fromSHA($this->github, $this->project, $commitStartSHA);
            } catch (RuntimeException $e) {
                throw new \RuntimeException("Commit \"{$commitStartSHA}\" does not exists: " . $e->getMessage());
            }
        }

        if ($commitEndSHA !== null) {
            try {
                $commitEnd = Commit::fromSHA($this->github, $this->project, $commitEndSHA);
            } catch (RuntimeException $e) {
                throw new \RuntimeException("Commit \"{$commitEndSHA}\" does not exists: " . $e->getMessage());
            }
        }

        return new Scope($commitStart, $commitEnd);
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

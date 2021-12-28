<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

use Aeon\Calendar\Gregorian\DateTime;
use Gitonomy\Git\Repository;

final class GitShell implements Git
{
    /**
     * @var RepositoryLocation
     */
    private RepositoryLocation $location;

    public function __construct(RepositoryLocation $location)
    {
        $this->location = $location;
    }

    public function tags() : Tags
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function branch(string $name) : Branch
    {
        $repository = new Repository($this->location->toString());
        $branch = $repository->getReferences()->getBranch($name);

        return new Branch(
            [
                'name' => $branch,
                'commit' => [
                    'sha' => $branch->getCommitHash(),
                ],
            ]
        );
    }

    public function currentBranch() : Branch
    {
        $repository = new Repository($this->location->toString());
        $branch = $repository->getHead();

        return new Branch(
            [
                'name' => $branch->getName(),
                'commit' => [
                    'sha' => $branch->getCommitHash(),
                ],
            ]
        );
    }

    public function putFile(string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA) : void
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function commitsCompare(Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function commits(string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function file(string $path, ?string $fileRef) : File
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function branches() : Branches
    {
        $repository = new Repository($this->location->toString());

        $branches = [];

        foreach ($repository->getReferences()->getLocalBranches() as $branch) {
            $branches[] = new Branch([
                'name' => $branch->getName(),
                'commit' => [
                    'sha' => $branch->getCommitHash(),
                ],
            ]);
        }

        return new Branches(...$branches);
    }

    public function tagCommit(Tag $tag) : Commit
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function referenceCommit(Reference $reference) : Commit
    {
        throw new \RuntimeException("Not implemented yet");
    }

    public function referenceTag(string $name) : Reference
    {
        throw new \RuntimeException("Not implemented yet");
    }
}

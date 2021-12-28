<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

use Aeon\Calendar\Gregorian\DateTime;

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
        // TODO: Implement tags() method.
    }

    public function branch(string $name) : Branch
    {
        // TODO: Implement branch() method.
    }

    public function currentBranch() : Branch
    {
        $repository = new \Gitonomy\Git\Repository($this->location->toString());

        return new Branch(
            [
                'name' => $repository->getHead()->getName(),
                'commit' => [
                    'sha' => $repository->getHead()->getCommitHash(),
                ],
            ]
        );
    }

    public function putFile(string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA) : void
    {
        // TODO: Implement putFile() method.
    }

    public function commitsCompare(Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits
    {
        // TODO: Implement commitsCompare() method.
    }

    public function commits(string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits
    {
        // TODO: Implement commits() method.
    }

    public function file(string $path, ?string $fileRef) : File
    {
        // TODO: Implement file() method.
    }

    public function branches() : Branches
    {
        $repository = new \Gitonomy\Git\Repository($this->location->toString());

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
        // TODO: Implement tagCommit() method.
    }

    public function referenceCommit(Reference $reference) : Commit
    {
        // TODO: Implement referenceCommit() method.
    }

    public function referenceTag(string $name) : Reference
    {
        // TODO: Implement referenceTag() method.
    }
}

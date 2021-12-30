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

    private ?Repository $repository = null;

    public function __construct(RepositoryLocation $location)
    {
        $this->location = $location;
    }

    public function tags() : Tags
    {
        $tags = [];
        /** @var \Gitonomy\Git\Reference\Tag $tag */
        foreach ($this->getRepository()->getReferences()->getTags() as $tag) {
            $tags[] = new Tag([
                'name' => $tag->getName(),
                'commit' => [
                    'sha' => $tag->getCommit()->getHash(),
                ],
            ]);
        }

        return new Tags(...$tags);
    }

    public function branch(string $name) : Branch
    {
        return $this->createBranch($this->getRepository()->getReferences()->getBranch($name));
    }

    public function currentBranch() : Branch
    {
        return $this->createBranch($this->getRepository()->getHead());
    }

    public function putFile(string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA = null) : void
    {
        \file_put_contents($path, $content);

        $this->getRepository()->run('add', [$path]);
        $this->getRepository()->run('commit', ['-a', '-m "' . $commitMessage . '"', '--author="' . $commiterName . '<' . $commiterEmail . '>"']);
    }

    public function commitsCompare(Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits
    {
        $revision = $this->getRepository()->getRevision($untilCommit->sha() . '...' . $fromCommit->sha());

        $commits = [];
        /** @var \Gitonomy\Git\Commit $commit */
        foreach ($revision->getLog() as $commit) {
            $commitObject = $this->createCommit($commit);

            if ($changedAfter) {
                if ($commitObject->date()->isBefore($changedAfter)) {
                    continue;
                }
            }

            if ($changedBefore) {
                if ($commitObject->date()->isAfter($changedBefore)) {
                    continue;
                }
            }

            $commits[] = $commitObject;
        }

        return new Commits(...$commits);
    }

    public function commit(string $sha) : Commit
    {
        return $this->createCommit($this->repository->getCommit($sha));
    }

    public function commits(string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits
    {
        $revision = $this->getRepository()->getRevision($sha);

        $commits = [];
        /** @var \Gitonomy\Git\Commit $commit */
        foreach ($revision->getLog() as $commit) {
            $commitObject = $this->createCommit($commit);

            if ($changedAfter) {
                if ($commitObject->date()->isBefore($changedAfter)) {
                    continue;
                }
            }

            if ($changedBefore) {
                if ($commitObject->date()->isAfter($changedBefore)) {
                    continue;
                }
            }

            $commits[] = $commitObject;
        }

        return new Commits(...$commits);
    }

    public function file(string $path, ?string $fileRef = null) : File
    {
        if (!\file_exists($path)) {
            throw new \InvalidArgumentException('File does not exists: ' . \realpath($path));
        }

        return new File([
            'name' => \basename($path),
            'sha' => \sha1(\file_get_contents($path)),
            'content' => \base64_encode(\file_get_contents($path)),
            'path' => \realpath($path),
        ]);
    }

    public function branches() : Branches
    {
        $branches = [];

        foreach ($this->getRepository()->getReferences()->getLocalBranches() as $branch) {
            $branches[] = $this->createBranch($branch);
        }

        return new Branches(...$branches);
    }

    public function tagCommit(Tag $tag) : Commit
    {
        return $this->createCommit($this->getRepository()->getCommit($tag->sha()));
    }

    public function referenceCommit(Reference $reference) : Commit
    {
        return $this->createCommit($this->getRepository()->getCommit($reference->sha()));
    }

    public function referenceTag(string $name) : Reference
    {
        $tag = $this->getRepository()->getReferences()->getTag($name);

        return new Reference(
            [
                'ref' => $tag->getFullname(),
                'object' => [
                    'sha' => $tag->getCommit()->getHash(),
                    'type' => 'tag',
                ],
            ]
        );
    }

    /**
     * @return Repository
     */
    private function getRepository() : Repository
    {
        if ($this->repository === null) {
            $this->repository = new Repository($this->location->toString());
        }

        return $this->repository;
    }

    /**
     * @param \Gitonomy\Git\Commit $commit
     *
     * @return Commit
     */
    private function createCommit(\Gitonomy\Git\Commit $commit) : Commit
    {
        return new Commit([
            'sha' => $commit->getHash(),
            'author' => [
                'login' => $commit->getAuthorName(),
                'html_url' => 'mailto:' . $commit->getAuthorEmail(),
            ],
            'commit' => [
                'author' => [
                    'email' => $commit->getAuthorEmail(),
                    'date' => $commit->getAuthorDate()->format('Y-m-d H:i:s.uP'),
                ],
                'message' => $commit->getMessage(),
            ],
            'html_url' => '#',
        ]);
    }

    /**
     * @param \Gitonomy\Git\Reference\Branch $branch
     *
     * @return Branch
     */
    private function createBranch(\Gitonomy\Git\Reference\Branch $branch) : Branch
    {
        return new Branch(
            [
                'name' => $branch->getName(),
                'commit' => [
                    'sha' => $branch->getCommitHash(),
                ],
            ]
        );
    }
}

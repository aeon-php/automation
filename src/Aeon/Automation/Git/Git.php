<?php declare(strict_types=1);

namespace Aeon\Automation\Git;

use Aeon\Calendar\Gregorian\DateTime;

interface Git
{
    public function tags() : Tags;

    public function branch(string $name) : Branch;

    public function currentBranch() : Branch;

    public function putFile(string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA) : void;

    public function commitsCompare(Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits;

    public function commits(string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits;

    public function file(string $path, ?string $fileRef) : File;

    public function branches() : Branches;

    public function tagCommit(Tag $tag) : Commit;

    public function referenceCommit(Reference $reference) : Commit;

    public function referenceTag(string $name) : Reference;
}

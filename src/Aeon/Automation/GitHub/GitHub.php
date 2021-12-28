<?php declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Calendar\Gregorian\DateTime;

interface GitHub
{
    public function branch(string $name) : Branch;

    public function branches() : Branches;

    public function commitPullRequests(Commit $commit) : PullRequests;

    public function commitsCompare(Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits;

    public function commits(string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits;

    public function pullRequest(int $number) : PullRequest;

    public function pullRequestsClosed(string $branch, int $limit) : PullRequests;

    public function pullRequestsOpen(string $branch, int $limit) : PullRequests;

    public function pullRequests(string $branch, string $state, int $limit) : PullRequests;

    public function referenceTag(string $name) : Reference;

    public function referenceCommit(Reference $reference) : Commit;

    public function repository() : Repository;

    public function milestones() : Milestones;

    public function createMilestone(string $title) : void;

    public function releases() : Releases;

    public function release(int $id) : Release;

    public function updateRelease(int $id, ?string $body = null) : Release;

    public function tags() : Tags;

    public function tagCommit(Tag $tag) : Commit;

    public function workflows() : Workflows;

    public function workflowLatestRun(Workflow $workflow) : ?WorkflowRun;

    public function workflowRunJobs(WorkflowRun $workflowRun) : WorkflowRunJobs;

    public function file(string $path, ?string $fileRef) : File;

    public function putFile(string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA) : void;
}

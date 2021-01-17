<?php declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;

interface GitHub
{
    public function branch(Project $project, string $name) : Branch;

    public function branches(Project $project) : Branches;

    public function commitPullRequests(Project $project, Commit $commit) : PullRequests;

    public function commitsCompare(Project $project, Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits;

    public function commits(Project $project, string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits;

    public function pullRequest(Project $project, int $number) : PullRequest;

    public function pullRequestsClosed(Project $project, string $branch, int $limit) : PullRequests;

    public function pullRequestsOpen(Project $project, string $branch, int $limit) : PullRequests;

    public function pullRequests(Project $project, string $branch, string $state, int $limit) : PullRequests;

    public function referenceTag(Project $project, string $name) : Reference;

    public function referenceCommit(Project $project, Reference $reference) : Commit;

    public function repository(Project $project) : Repository;

    public function milestones(Project $project) : Milestones;

    public function createMilestone(Project $project, string $title) : void;

    public function releases(Project $project) : Releases;

    public function release(Project $project, int $id) : Release;

    public function updateRelease(Project $project, int $id, ?string $body = null) : Release;

    public function tags(Project $project) : Tags;

    public function tagCommit(Project $project, Tag $tag) : Commit;

    public function workflows(Project $project) : Workflows;

    public function workflowLatestRun(Project $project, Workflow $workflow) : ?WorkflowRun;

    public function workflowRunJobs(Project $project, WorkflowRun $workflowRun) : WorkflowRunJobs;

    public function file(Project $project, string $path, ?string $fileRef) : File;

    public function putFile(Project $project, string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA) : void;
}

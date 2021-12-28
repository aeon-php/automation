<?php declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Git\Commit;
use Aeon\Automation\Git\Git;

interface GitHub extends Git
{
    public function repository() : Repository;

    public function commitPullRequests(Commit $commit) : PullRequests;

    public function pullRequest(int $number) : PullRequest;

    public function pullRequestsClosed(string $branch, int $limit) : PullRequests;

    public function pullRequestsOpen(string $branch, int $limit) : PullRequests;

    public function pullRequests(string $branch, string $state, int $limit) : PullRequests;

    public function milestones() : Milestones;

    public function createMilestone(string $title) : void;

    public function releases() : Releases;

    public function release(int $id) : Release;

    public function updateRelease(int $id, ?string $body = null) : Release;

    public function workflows() : Workflows;

    public function workflowLatestRun(Workflow $workflow) : ?WorkflowRun;

    public function workflowRunJobs(WorkflowRun $workflowRun) : WorkflowRunJobs;
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;

final class GitHubClient implements GitHub
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function branch(Project $project, string $name) : Branch
    {
        return new Branch($this->client->repo()->branches($project->organization(), $project->name(), $name));
    }

    public function branches(Project $project) : Branches
    {
        return new Branches(...\array_map(
            fn (array $branchData) : Branch => new Branch($branchData),
            $this->client->repository()->branches($project->organization(), $project->name())
        ));
    }

    public function commitPullRequests(Project $project, Commit $commit) : PullRequests
    {
        $pullRequestsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/commits/' . \rawurlencode($commit->sha()) . '/pulls',
                ['Accept' => 'application/vnd.github.groot-preview+json']
            )
        );

        return new PullRequests(...\array_map(fn (array $pullRequestData) : PullRequest => new PullRequest($pullRequestData), $pullRequestsData));
    }

    public function commitsCompare(Project $project, Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits
    {
        $commitsPaginator = new ResultPager($this->client);
        $commitsData = $commitsPaginator->fetch($this->client->repo()->commits(), 'compare', [$project->organization(), $project->name(), $untilCommit->sha(), $fromCommit->sha()]);

        $totalCommits = $commitsData['total_commits'];
        $commitsData = $commitsData['commits'];

        $remainingCommitsCount = $totalCommits - \count($commitsData);

        $commits = new Commits(
            ...\array_map(
                fn (array $commitData) : Commit => new Commit($commitData),
                \array_reverse($commitsData)
            )
        );

        // compare API has limit to return maximum 250 commits in the chronological order
        // in order to get more commits we need to start from the first one (it's the last on in the history)
        // and use commits list API (skipping first one to avoid duplicates) to get remaining commits.
        if ($remainingCommitsCount > 0) {
            $remainingCommits = $this->commits($project, \current($commitsData)['sha'], $changedAfter, $changedBefore, $remainingCommitsCount + 1);
            $commits = $commits->merge($remainingCommits->skip(1));
        }

        return $commits;
    }

    public function commit(Project $project, string $sha) : Commit
    {
        return new Commit($this->client->repo()->commits()->show($project->organization(), $project->name(), $sha));
    }

    public function commits(Project $project, string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits
    {
        $parameters = ['sha' => $sha];

        if ($changedAfter !== null) {
            $parameters['since'] = $changedAfter->toISO8601();
        }

        if ($changedBefore !== null) {
            $parameters['until'] = $changedBefore->toISO8601();
        }

        $commitsPaginator = new ResultPager($this->client);
        $commitsData = $commitsPaginator->fetch($this->client->repo()->commits(), 'all', [$project->organization(), $project->name(), $parameters]);

        $foundAll = false;

        $commits = [];
        $totalCommits = 0;

        while ($foundAll === false) {
            foreach ($commitsData as $commitData) {
                $commit = new Commit($commitData);

                if ($limit !== null && $totalCommits >= $limit) {
                    $foundAll = true;

                    break;
                }

                $commits[] = $commit;
                $totalCommits += 1;
            }

            if ($foundAll) {
                break;
            }

            if ($commitsPaginator->hasNext()) {
                $commitsData = $commitsPaginator->fetchNext();
            } else {
                break;
            }
        }

        return new Commits(...$commits);
    }

    public function pullRequest(Project $project, int $number) : PullRequest
    {
        return new PullRequest($this->client->pullRequests()->show($project->organization(), $project->name(), $number));
    }

    public function pullRequestsClosed(Project $project, string $branch, int $limit) : PullRequests
    {
        return $this->pullRequests($project, $branch, 'closed', $limit);
    }

    public function pullRequestsOpen(Project $project, string $branch, int $limit) : PullRequests
    {
        return $this->pullRequests($project, $branch, 'open', $limit);
    }

    public function pullRequests(Project $project, string $branch, string $state, int $limit) : PullRequests
    {
        $pullsPaginator = new ResultPager($this->client);
        $pullsData = $pullsPaginator->fetch(
            $this->client->pullRequests(),
            'all',
            [
                $project->organization(),
                $project->name(),
                [
                    'state' => $state,
                    'base' => $branch,
                ],
            ]
        );

        $pullRequests = [];

        while (true) {
            foreach ($pullsData as $pullData) {
                $pullRequests[] = new PullRequest($pullData);

                if (\count($pullRequests) >= $limit) {
                    break;
                }
            }

            if (\count($pullRequests) >= $limit) {
                break;
            }

            if ($pullsPaginator->hasNext()) {
                $pullsData = $pullsPaginator->fetchNext();
            } else {
                break;
            }
        }

        return new PullRequests(...$pullRequests);
    }

    public function referenceTag(Project $project, string $name) : Reference
    {
        return new Reference($this->client->gitData()->references()->show($project->organization(), $project->name(), 'tags/' . $name));
    }

    public function referenceCommit(Project $project, Reference $reference) : Commit
    {
        if ($reference->type() === 'tag') {
            $tagData = $this->client->gitData()->tags()->show($project->organization(), $project->name(), $reference->sha());

            return new Commit($this->client->repo()->commits()->show($project->organization(), $project->name(), $tagData['object']['sha']));
        }

        return new Commit($this->client->repo()->commits()->show($project->organization(), $project->name(), $reference->sha()));
    }

    public function repository(Project $project) : Repository
    {
        return new Repository($this->client->repositories()->show($project->organization(), $project->name()));
    }

    public function milestones(Project $project) : Milestones
    {
        $milestonePaginator = new ResultPager($this->client);
        $milestoneData = $milestonePaginator->fetchAll($this->client->issue()->milestones(), 'all', [$project->organization(), $project->name(), ['state' => 'all']]);

        return new Milestones(...\array_map(
            fn (array $milestoneData) : Milestone => new Milestone($milestoneData),
            $milestoneData
        ));
    }

    public function createMilestone(Project $project, string $title) : void
    {
        $this->client->issue()->milestones()->create($project->organization(), $project->name(), ['title' => $title]);
    }

    public function releases(Project $project) : Releases
    {
        $releasePaginator = new ResultPager($this->client);
        $releasesData = $releasePaginator->fetchAll($this->client->repository()->releases(), 'all', [$project->organization(), $project->name()]);

        return new Releases(...\array_map(
            fn (array $releaseData) : Release => new Release($releaseData),
            $releasesData
        ));
    }

    public function tags(Project $project) : Tags
    {
        $tagsPaginator = new ResultPager($this->client);
        $tagsData = $tagsPaginator->fetchAll($this->client->repo(), 'tags', [$project->organization(), $project->name()]);

        return new Tags(...\array_map(
            fn (array $tagData) : Tag => new Tag($tagData),
            $tagsData
        ));
    }

    public function tagCommit(Project $project, Tag $tag) : Commit
    {
        return new Commit($this->client->repo()->commits()->show($project->organization(), $project->name(), $tag->sha()));
    }

    public function workflows(Project $project) : Workflows
    {
        $workflowsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/actions/workflows',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new Workflows(...\array_map(
            fn (array $workflowData) : Workflow => new Workflow($workflowData),
            $workflowsData['workflows']
        ));
    }

    public function workflowLatestRun(Project $project, Workflow $workflow) : ?WorkflowRun
    {
        $runsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/actions/workflows/' . $workflow->id() . '/runs',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        if (\count($runsData['workflow_runs']) === 0) {
            return null;
        }

        return new WorkflowRun(\current($runsData['workflow_runs']));
    }

    public function workflowRunJobs(Project $project, WorkflowRun $workflowRun) : WorkflowRunJobs
    {
        $jobsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/actions/runs/' . $workflowRun->id() . '/jobs',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new WorkflowRunJobs(...\array_map(fn (array $jobData) : WorkflowRunJob => new WorkflowRunJob($jobData), $jobsData['jobs']));
    }
}

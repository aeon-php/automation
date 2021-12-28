<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Git\Branch;
use Aeon\Automation\Git\Branches;
use Aeon\Automation\Git\Commit;
use Aeon\Automation\Git\Commits;
use Aeon\Automation\Git\File;
use Aeon\Automation\Git\Reference;
use Aeon\Automation\Git\Repository;
use Aeon\Automation\Git\Tag;
use Aeon\Automation\Git\Tags;
use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;
use Psr\Cache\CacheItemPoolInterface;

final class GitHubClient implements GitHub
{
    private Project $project;

    private Client $client;

    private CacheItemPoolInterface $cache;

    public function __construct(Project $project, Client $client, CacheItemPoolInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->project = $project;
    }

    public function branch(string $name) : Branch
    {
        return new Branch($this->client->repo()->branches($this->project->organization(), $this->project->name(), $name));
    }

    public function branches() : Branches
    {
        return new Branches(...\array_map(
            fn (array $branchData) : Branch => new Branch($branchData),
            $this->client->repository()->branches($this->project->organization(), $this->project->name())
        ));
    }

    public function commitPullRequests(Commit $commit) : PullRequests
    {
        $pullRequestsCacheItem = $this->cache->getItem("github.{$this->project->organization()}.{$this->project->name()}.commit.{$commit->sha()}.pull_requests");

        if (!$pullRequestsCacheItem->isHit()) {
            $pullRequestsData = ResponseMediator::getContent(
                $this->client->getHttpClient()->get(
                    '/repos/' . \rawurlencode($this->project->organization()) . '/' . \rawurlencode($this->project->name()) . '/commits/' . \rawurlencode($commit->sha()) . '/pulls',
                    ['Accept' => 'application/vnd.github.groot-preview+json']
                )
            );

            $pullRequestsCacheItem->set($pullRequestsData);
            $this->cache->save($pullRequestsCacheItem);
        } else {
            $pullRequestsData = $pullRequestsCacheItem->get();
        }

        return new PullRequests(...\array_map(fn (array $pullRequestData) : PullRequest => new PullRequest($pullRequestData), $pullRequestsData));
    }

    public function commitsCompare(Commit $fromCommit, Commit $untilCommit, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null) : Commits
    {
        $commitsPaginator = new ResultPager($this->client);
        $commitsData = $commitsPaginator->fetch($this->client->repo()->commits(), 'compare', [$this->project->organization(), $this->project->name(), $untilCommit->sha(), $fromCommit->sha()]);

        $commitsData = $commitsData['commits'];

        $commits = new Commits();

        while (true) {
            foreach ($commitsData as $commitData) {
                $commits = $commits->merge(new Commits(new Commit($commitData)));
            }

            if ($commitsPaginator->hasNext()) {
                $commitsData = $commitsPaginator->fetchNext()['commits'];
            } else {
                break;
            }
        }

        return $commits;
    }

    public function commit(string $sha) : Commit
    {
        return new Commit($this->client->repo()->commits()->show($this->project->organization(), $this->project->name(), $sha));
    }

    public function commits(string $sha, ?DateTime $changedAfter = null, ?DateTime $changedBefore = null, ?int $limit = null) : Commits
    {
        $parameters = ['sha' => $sha];

        if ($changedAfter !== null) {
            $parameters['since'] = $changedAfter->toISO8601();
        }

        if ($changedBefore !== null) {
            $parameters['until'] = $changedBefore->toISO8601();
        }

        $commitsPaginator = new ResultPager($this->client);
        $commitsData = $commitsPaginator->fetch($this->client->repo()->commits(), 'all', [$this->project->organization(), $this->project->name(), $parameters]);

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

    public function pullRequest(int $number) : PullRequest
    {
        return new PullRequest($this->client->pullRequests()->show($this->project->organization(), $this->project->name(), $number));
    }

    public function pullRequestsClosed(string $branch, int $limit) : PullRequests
    {
        return $this->pullRequests($project, $branch, 'closed', $limit);
    }

    public function pullRequestsOpen(string $branch, int $limit) : PullRequests
    {
        return $this->pullRequests($project, $branch, 'open', $limit);
    }

    public function pullRequests(string $branch, string $state, int $limit) : PullRequests
    {
        $pullsPaginator = new ResultPager($this->client);
        $pullsData = $pullsPaginator->fetch(
            $this->client->pullRequests(),
            'all',
            [
                $this->project->organization(),
                $this->project->name(),
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

    public function referenceTag(string $name) : Reference
    {
        $referenceCacheItem = $this->cache->getItem("github.{$this->project->organization()}.{$this->project->name()}.reference_tag.{$name}");

        if (!$referenceCacheItem->isHit()) {
            $referenceTagData = $this->client->gitData()->references()->show($this->project->organization(), $this->project->name(), 'tags/' . $name);

            $referenceCacheItem->set($referenceTagData);
            $this->cache->save($referenceCacheItem);
        } else {
            $referenceTagData = $referenceCacheItem->get();
        }

        return new Reference($referenceTagData);
    }

    public function referenceCommit(Reference $reference) : Commit
    {
        if ($reference->type() === 'tag') {
            $tagData = $this->client->gitData()->tags()->show($this->project->organization(), $this->project->name(), $reference->sha());

            return new Commit($this->client->repo()->commits()->show($this->project->organization(), $this->project->name(), $tagData['object']['sha']));
        }

        return new Commit($this->client->repo()->commits()->show($this->project->organization(), $this->project->name(), $reference->sha()));
    }

    public function repository() : Repository
    {
        $repositoryCacheItem = $this->cache->getItem("github.{$this->project->organization()}.{$this->project->name()}.repository");

        if (!$repositoryCacheItem->isHit()) {
            $repositoryData = $this->client->repositories()->show($this->project->organization(), $this->project->name());

            $repositoryCacheItem->set($repositoryData);
            $this->cache->save($repositoryCacheItem);
        } else {
            $repositoryData = $repositoryCacheItem->get();
        }

        return new Repository($repositoryData);
    }

    public function milestones() : Milestones
    {
        $milestonePaginator = new ResultPager($this->client);
        $milestoneData = $milestonePaginator->fetchAll($this->client->issue()->milestones(), 'all', [$this->project->organization(), $this->project->name(), ['state' => 'all']]);

        return new Milestones(...\array_map(
            fn (array $milestoneData) : Milestone => new Milestone($milestoneData),
            $milestoneData
        ));
    }

    public function createMilestone(string $title) : void
    {
        $this->client->issue()->milestones()->create($this->project->organization(), $this->project->name(), ['title' => $title]);
    }

    public function releases() : Releases
    {
        $releasePaginator = new ResultPager($this->client);
        $releasesData = $releasePaginator->fetchAll($this->client->repository()->releases(), 'all', [$this->project->organization(), $this->project->name()]);

        return new Releases(...\array_map(
            fn (array $releaseData) : Release => new Release($releaseData),
            $releasesData
        ));
    }

    public function release(int $id) : Release
    {
        return new Release($this->client->repository()->releases()->show($this->project->organization(), $this->project->name(), $id));
    }

    public function updateRelease(int $id, ?string $body = null) : Release
    {
        $parameters = [];

        if ($body !== null) {
            $parameters['body'] = $body;
        }

        return new Release($this->client->repository()->releases()->edit($this->project->organization(), $this->project->name(), $id, $parameters));
    }

    public function tags() : Tags
    {
        $tagsPaginator = new ResultPager($this->client);
        $tagsData = $tagsPaginator->fetchAll($this->client->repo(), 'tags', [$this->project->organization(), $this->project->name()]);

        return new Tags(...\array_map(
            fn (array $tagData) : Tag => new Tag($tagData),
            $tagsData
        ));
    }

    public function tagCommit(Tag $tag) : Commit
    {
        return new Commit($this->client->repo()->commits()->show($this->project->organization(), $this->project->name(), $tag->sha()));
    }

    public function workflows() : Workflows
    {
        $workflowsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($this->project->organization()) . '/' . \rawurlencode($this->project->name()) . '/actions/workflows',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new Workflows(...\array_map(
            fn (array $workflowData) : Workflow => new Workflow($workflowData),
            $workflowsData['workflows']
        ));
    }

    public function workflowLatestRun(Workflow $workflow) : ?WorkflowRun
    {
        $runsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($this->project->organization()) . '/' . \rawurlencode($this->project->name()) . '/actions/workflows/' . $workflow->id() . '/runs',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        if (\count($runsData['workflow_runs']) === 0) {
            return null;
        }

        return new WorkflowRun(\current($runsData['workflow_runs']));
    }

    public function workflowRunJobs(WorkflowRun $workflowRun) : WorkflowRunJobs
    {
        $jobsData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($this->project->organization()) . '/' . \rawurlencode($this->project->name()) . '/actions/runs/' . $workflowRun->id() . '/jobs',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new WorkflowRunJobs(...\array_map(fn (array $jobData) : WorkflowRunJob => new WorkflowRunJob($jobData), $jobsData['jobs']));
    }

    public function workflowTiming(Workflow $workflow) : WorkflowTiming
    {
        $timingData = ResponseMediator::getContent(
            $this->client->getHttpClient()->get(
                '/repos/' . \rawurlencode($this->project->organization()) . '/' . \rawurlencode($this->project->name()) . '/actions/workflows/' . $workflow->id() . '/timing',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new WorkflowTiming($timingData);
    }

    public function file(string $path, ?string $fileRef) : File
    {
        return new File($this->client->repo()->contents()->show($this->project->organization(), $this->project->name(), $path, $fileRef));
    }

    public function putFile(string $path, string $commitMessage, string $commiterName, string $commiterEmail, string $content, ?string $fileSHA) : void
    {
        if ($fileSHA) {
            $this->client->repo()->contents()->update(
                $this->project->organization(),
                $this->project->name(),
                $path,
                $content,
                $commitMessage,
                $fileSHA,
                null,
                [
                    'name' => $commiterName,
                    'email' => $commiterEmail,
                ]
            );
        } else {
            $this->client->repo()->contents()->create(
                $this->project->organization(),
                $this->project->name(),
                $path,
                $content,
                $commitMessage,
                null,
                [
                    'name' => $commiterName,
                    'email' => $commiterEmail,
                ]
            );
        }
    }
}

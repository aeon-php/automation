<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;

final class WorkflowRun
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function jobs(Client $client, Project $project) : WorkflowRunJobs
    {
        $jobsData = ResponseMediator::getContent(
            $client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/actions/runs/' . $this->data['id'] . '/jobs',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new WorkflowRunJobs(...\array_map(fn (array $jobData) : WorkflowRunJob => new WorkflowRunJob($jobData), $jobsData['jobs']));
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;

final class Workflow
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function latestRun(Client $client, Project $project) : ?WorkflowRun
    {
        $runsData = ResponseMediator::getContent(
            $client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/actions/workflows/' . $this->data['id'] . '/runs',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        if (\count($runsData['workflow_runs']) === 0) {
            return null;
        }

        return new WorkflowRun(\current($runsData['workflow_runs']));
    }

    public function name() : string
    {
        return $this->data['name'];
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;
use Github\HttpClient\Message\ResponseMediator;

final class Workflows
{
    /**
     * @var Workflow[]
     */
    private array $workflows;

    public function __construct(Workflow ...$tags)
    {
        $this->workflows = $tags;
    }

    public static function getAll(Client $client, Project $project) : self
    {
        $workflowsData = ResponseMediator::getContent(
            $client->getHttpClient()->get(
                '/repos/' . \rawurlencode($project->organization()) . '/' . \rawurlencode($project->name()) . '/actions/workflows',
                ['Accept' => 'application/vnd.github.v3+json']
            )
        );

        return new self(...\array_map(
            fn (array $workflowData) : Workflow => new Workflow($workflowData),
            $workflowsData['workflows']
        ));
    }

    /**
     * @return Workflow[]
     */
    public function all()
    {
        return $this->workflows;
    }

    public function count() : int
    {
        return \count($this->workflows);
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;

final class Tag
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function name() : string
    {
        return $this->data['name'];
    }

    public function commit(Client $client, Project $project) : Commit
    {
        return new Commit($client->gitData()->commits()->show($project->organization(), $project->name(), $this->data['commit']['sha']));
    }
}

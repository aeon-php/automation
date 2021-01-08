<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;

final class Repository
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromProject(Client $client, Project $project) : self
    {
        return new self($client->repositories()->show($project->organization(), $project->name()));
    }

    public function defaultBranch() : string
    {
        return $this->data['default_branch'];
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;

final class Branch
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function byName(Client $client, Project $project, string $name) : self
    {
        return new self($client->repo()->branches($project->organization(), $project->name(), $name));
    }

    public function name() : string
    {
        return $this->data['name'];
    }

    public function sha() : string
    {
        return $this->data['commit']['sha'];
    }

    public function isDefault(Repository $repository) : bool
    {
        return $this->name() === $repository->defaultBranch();
    }
}

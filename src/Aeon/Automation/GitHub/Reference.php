<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;

final class Reference
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function tag(Client $client, Project $project, string $name) : self
    {
        return new self($client->gitData()->references()->show($project->organization(), $project->name(), 'tags/' . $name));
    }

    public function sha() : string
    {
        return $this->data['object']['sha'];
    }

    public function ref() : string
    {
        return $this->data['ref'];
    }

    public function tagName() : ?string
    {
        return \strpos($this->data['ref'], 'refs/tags/') === 0 ? \str_replace('refs/tags/', '', $this->data['ref']) : null;
    }

    public function commit(Client $client, Project $project) : Commit
    {
        if ($this->data['object']['type'] === 'tag') {
            $tagData = $client->gitData()->tags()->show($project->organization(), $project->name(), $this->sha());

            return new Commit($client->repo()->commits()->show($project->organization(), $project->name(), $tagData['object']['sha']));
        }

        return new Commit($client->repo()->commits()->show($project->organization(), $project->name(), $this->sha()));
    }
}

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

    public static function commitFromString(Client $client, Project $project, string $name) : self
    {
        $reference = new self($client->gitData()->references()->show($project->organization(), $project->name(), $name));

        if ($reference->isTag()) {
            return new self($client->gitData()->tags()->show($project->organization(), $project->name(), $reference->sha()));
        }

        return $reference;
    }

    public function sha() : string
    {
        return $this->data['object']['sha'];
    }

    public function isCommit() : bool
    {
        return $this->data['object']['type'] === 'commit';
    }

    public function isTag() : bool
    {
        return $this->data['object']['type'] === 'tag';
    }

    public function commit(Client $client, Project $project) : Commit
    {
        if (!$this->isCommit()) {
            throw new \RuntimeException('Reference is not a commit');
        }

        return new Commit($client->gitData()->commits()->show($project->organization(), $project->name(), $this->sha()));
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Github\Client;

final class Branches
{
    /**
     * @var Branch[]
     */
    private array $branches;

    public function __construct(Branch ...$branches)
    {
        $this->branches = $branches;
    }

    public static function getAll(Client $client, Project $project) : self
    {
        return new self(...\array_map(
            fn (array $branchData) : Branch => new Branch($branchData),
            $client->repository()->branches($project->organization(), $project->name())
        ));
    }

    /**
     * @return Branch[]
     */
    public function all()
    {
        return $this->branches;
    }
}

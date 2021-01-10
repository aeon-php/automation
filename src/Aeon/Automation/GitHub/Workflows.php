<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

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

    /**
     * @return Workflow[]
     */
    public function all() : array
    {
        return $this->workflows;
    }

    public function count() : int
    {
        return \count($this->workflows);
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class WorkflowRunJobs
{
    /**
     * @var WorkflowRunJob[]
     */
    private array $jobs;

    public function __construct(WorkflowRunJob ...$jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * @return WorkflowRunJob[]
     */
    public function all() : array
    {
        return $this->jobs;
    }
}

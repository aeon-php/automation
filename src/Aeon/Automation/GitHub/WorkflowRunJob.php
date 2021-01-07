<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class WorkflowRunJob
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

    public function isCompleted() : bool
    {
        return $this->data['status'] === 'completed';
    }

    public function isSuccessful() : bool
    {
        return $this->isCompleted() && $this->data['conclusion'] === 'success';
    }
}

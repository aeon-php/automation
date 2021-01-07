<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Calendar\Gregorian\DateTime;

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

    public function completedAt() : ?DateTime
    {
        return $this->isCompleted() ? DateTime::fromString($this->data['completed_at']) : null;
    }
}

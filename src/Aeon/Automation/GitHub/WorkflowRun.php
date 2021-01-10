<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class WorkflowRun
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function id() : int
    {
        return $this->data['id'];
    }
}

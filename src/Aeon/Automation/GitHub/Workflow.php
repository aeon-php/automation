<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class Workflow
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

    public function name() : string
    {
        return $this->data['name'];
    }
}

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

    public function path() : string
    {
        return $this->data['path'];
    }

    public function state() : string
    {
        return $this->data['state'];
    }

    public function isActive() : bool
    {
        return $this->data['state'] === 'active';
    }
}

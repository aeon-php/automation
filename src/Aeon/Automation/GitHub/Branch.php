<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class Branch
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

    public function sha() : string
    {
        return $this->data['commit']['sha'];
    }

    public function isDefault(Repository $repository) : bool
    {
        return $this->name() === $repository->defaultBranch();
    }
}

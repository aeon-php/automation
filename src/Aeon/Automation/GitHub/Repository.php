<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class Repository
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function defaultBranch() : string
    {
        return $this->data['default_branch'];
    }
}

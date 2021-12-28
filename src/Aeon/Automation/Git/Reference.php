<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

final class Reference
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sha() : string
    {
        return $this->data['object']['sha'];
    }

    public function ref() : string
    {
        return $this->data['ref'];
    }

    public function tagName() : ?string
    {
        return \strpos($this->data['ref'], 'refs/tags/') === 0 ? \str_replace('refs/tags/', '', $this->data['ref']) : null;
    }

    public function type() : string
    {
        return $this->data['object']['type'];
    }
}

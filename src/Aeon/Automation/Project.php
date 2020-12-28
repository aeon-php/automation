<?php

declare(strict_types=1);

namespace Aeon\Automation;

final class Project
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function is(string $name) : bool
    {
        return \strtolower($this->name) === \strtolower($name);
    }

    public function fullName() : string
    {
        return $this->name;
    }

    public function organization() : string
    {
        return \explode('/', $this->name)[0];
    }

    public function name() : string
    {
        return \explode('/', $this->name)[1];
    }
}

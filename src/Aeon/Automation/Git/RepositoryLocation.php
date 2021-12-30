<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

final class RepositoryLocation
{
    private string $location;

    public function __construct(string $location)
    {
        if (!\file_exists($location)) {
            throw new \InvalidArgumentException("Location does not exists: {$location}");
        }
        $this->location = $location;
    }

    public function toString() : string
    {
        return \realpath($this->location);
    }
}

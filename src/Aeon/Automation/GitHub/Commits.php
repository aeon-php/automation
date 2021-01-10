<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class Commits
{
    /**
     * @var Commit[]
     */
    private array $commits;

    public function __construct(Commit ...$commits)
    {
        $this->commits = $commits;
    }

    public function merge(self $commits) : self
    {
        return new self(...\array_merge(
            $this->commits,
            $commits->commits
        ));
    }

    public function count() : int
    {
        return \count($this->commits);
    }

    /**
     * @return Commit[]
     */
    public function all() : array
    {
        return $this->commits;
    }

    public function skip(int $commits) : self
    {
        if ($commits >= $this->count()) {
            return new self();
        }

        return new self(...\array_slice($this->commits, $commits, $this->count() -1));
    }
}

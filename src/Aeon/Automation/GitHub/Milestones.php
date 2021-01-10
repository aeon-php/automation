<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

final class Milestones
{
    /**
     * @var Milestone[]
     */
    private array $milestones;

    public function __construct(Milestone ...$milestones)
    {
        $this->milestones = $milestones;
    }

    /**
     * @return Milestone[]
     */
    public function all() : array
    {
        return $this->milestones;
    }

    public function onlyValidSemVer() : self
    {
        $parser = new VersionParser();

        $milestones = [];

        foreach ($this->milestones as $milestone) {
            try {
                $parser->normalize($milestone->title());
                $milestones[] = $milestone;
            } catch (\UnexpectedValueException $e) {
            }
        }

        return new self(...$milestones);
    }

    public function semVerRsort() : self
    {
        $sortedNames = Semver::rsort(\array_map(fn (Milestone $milestone) : string => $milestone->title(), $this->onlyValidSemVer()->all()));
        $milestones = [];

        foreach ($sortedNames as $sortedName) {
            foreach ($this->milestones as $milestone) {
                if ($milestone->title() === $sortedName) {
                    $milestones[] = $milestone;
                }
            }
        }

        return new self(...$milestones);
    }

    public function semVerSort() : self
    {
        $sortedNames = Semver::sort(\array_map(fn (Milestone $milestone) : string => $milestone->title(), $this->onlyValidSemVer()->all()));
        $milestones = [];

        foreach ($sortedNames as $sortedName) {
            foreach ($this->milestones as $milestone) {
                if ($milestone->title() === $sortedName) {
                    $milestones[] = $milestone;
                }
            }
        }

        return new self(...$milestones);
    }

    public function first() : ?Milestone
    {
        if (!$this->count()) {
            return null;
        }

        return \current($this->milestones);
    }

    public function last() : ?Milestone
    {
        if (!$this->count()) {
            return null;
        }

        return \end($this->milestones);
    }

    public function next(string $milestone) : ?Milestone
    {
        $found = false;

        foreach ($this->milestones as $nextMilestone) {
            if ($found) {
                return $nextMilestone;
            }

            if ($nextMilestone->title() === $milestone) {
                $found = true;
            }
        }

        return null;
    }

    public function count() : int
    {
        return \count($this->milestones);
    }

    public function limit(int $limit) : self
    {
        return new self(...\array_slice($this->milestones, 0, $limit));
    }

    public function exists(string $title) : bool
    {
        foreach ($this->milestones as $milestone) {
            if ($milestone->title() === $title) {
                return true;
            }
        }

        return false;
    }
}

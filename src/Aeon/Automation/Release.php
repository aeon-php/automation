<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Contributor;
use Aeon\Automation\Changes\Type;
use Aeon\Calendar\Gregorian\Day;

final class Release
{
    private string $name;

    private Day $day;

    /**
     * @var Changes[]
     */
    private array $changes;

    public function __construct(string $name, Day $day)
    {
        $this->name = $name;
        $this->day = $day;
        $this->changes = [];
    }

    public function update(string $newName, Day $newDay) : self
    {
        $release = new self($newName, $newDay);
        $release->changes = $this->changes;

        return $release;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function isUnreleased() : bool
    {
        return \strtolower($this->name) === 'unreleased';
    }

    public function day() : Day
    {
        return $this->day;
    }

    /**
     * @return Changes[]
     */
    public function changes() : array
    {
        $changes = $this->changes;

        \uasort($changes, function (Changes $changeA, Changes $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $changes;
    }

    /**
     * Only one set of changes is allowed for a single source. This prevents duplications when
     * change comes from Commit and Pull Request that merged that commit.
     */
    public function add(Changes $changes) : void
    {
        foreach ($this->changes as $change) {
            if ($change->source()->equals($changes->source())) {
                return;
            }
        }

        $this->changes[] = $changes;
    }

    public function hasFrom(ChangesSource $source) : bool
    {
        foreach ($this->changes as $change) {
            if ($change->source()->equals($source)) {
                return true;
            }
        }

        return false;
    }

    public function getFrom(ChangesSource $source) : Changes
    {
        foreach ($this->changes as $changes) {
            if ($changes->source()->equals($source)) {
                return $changes;
            }
        }

        throw new \InvalidArgumentException("There are no changes in this release from source with id: {$source->id()}");
    }

    /**
     * @return Change[]
     */
    public function all() : array
    {
        return $this->sortChanges();
    }

    /**
     * @return Change[]
     */
    public function added() : array
    {
        return $this->sortChanges(Type::added());
    }

    /**
     * @return Change[]
     */
    public function changed() : array
    {
        return $this->sortChanges(Type::changed());
    }

    /**
     * @return Change[]
     */
    public function updated() : array
    {
        return $this->sortChanges(Type::updated());
    }

    /**
     * @return Change[]
     */
    public function fixed() : array
    {
        return $this->sortChanges(Type::fixed());
    }

    /**
     * @return Change[]
     */
    public function removed() : array
    {
        return $this->sortChanges(Type::removed());
    }

    /**
     * @return Change[]
     */
    public function deprecated() : array
    {
        return $this->sortChanges(Type::deprecated());
    }

    /**
     * @return Change[]
     */
    public function security() : array
    {
        return $this->sortChanges(Type::security());
    }

    /**
     * @return array<Contributor>
     */
    public function contributors() : array
    {
        /** @var array<string, Contributor> $contributors */
        $contributors = [];

        foreach ($this->changes as $changes) {
            foreach ($changes->all() as $change) {
                if (!\array_key_exists($change->source()->contributor()->name(), $contributors)) {
                    $contributors[$change->source()->contributor()->name()] = $change->source()->contributor();
                }
            }
        }

        \uasort($contributors, static function (Contributor $authorA, Contributor $authorB) : int {
            return \strtolower($authorA->name()) <=> \strtolower($authorB->name());
        });

        return \array_values($contributors);
    }

    public function empty() : bool
    {
        return \count($this->all()) === 0;
    }

    public function replace(Changes $changes) : void
    {
        $newChanges = [];

        foreach ($this->changes as $existingChanges) {
            $newChanges[] = $existingChanges->source()->equals($changes->source()) ? $changes : $existingChanges;
        }

        $this->changes = $newChanges;
    }

    public function isEqual(self $release) : bool
    {
        if (\count($this->all()) !== \count($release->all())) {
            return false;
        }

        $changeIds = [];

        foreach ($this->all() as $change) {
            $changeIds[] = $change->id();
        }

        foreach ($release->all() as $releaseChange) {
            if (!\in_array($releaseChange->id(), $changeIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Change[]
     */
    private function sortChanges(?Type $type = null) : array
    {
        $changes = \array_merge(
            ...\array_map(fn (Changes $changes) : array => ($type === null) ? $changes->all() : $changes->withType($type), $this->changes())
        );

        \uasort($changes, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $changes;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesSource;
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

    public function day() : Day
    {
        return $this->day;
    }

    /**
     * @return Changes[]
     */
    public function changes() : array
    {
        \uasort($this->changes, function (Changes $changeA, Changes $changeB) : int {
            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $this->changes;
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
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->all(), $this->changes())
        );
    }

    /**
     * @return Change[]
     */
    public function added() : array
    {
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::added()), $this->changes())
        );
    }

    /**
     * @return Change[]
     */
    public function changed() : array
    {
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::changed()), $this->changes())
        );
    }

    /**
     * @return Change[]
     */
    public function fixed() : array
    {
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::fixed()), $this->changes())
        );
    }

    /**
     * @return Change[]
     */
    public function removed() : array
    {
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::removed()), $this->changes())
        );
    }

    /**
     * @return Change[]
     */
    public function deprecated() : array
    {
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::deprecated()), $this->changes())
        );
    }

    /**
     * @return Change[]
     */
    public function security() : array
    {
        return \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::security()), $this->changes())
        );
    }

    public function empty() : bool
    {
        return \count($this->all()) === 0;
    }
}

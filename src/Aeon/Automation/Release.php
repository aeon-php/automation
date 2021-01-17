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
        $all = \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->all(), $this->changes())
        );

        \uasort($all, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $all;
    }

    /**
     * @return Change[]
     */
    public function added() : array
    {
        $added = \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::added()), $this->changes())
        );

        \uasort($added, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $added;
    }

    /**
     * @return Change[]
     */
    public function changed() : array
    {
        $changed = \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::changed()), $this->changes())
        );

        \uasort($changed, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $changed;
    }

    /**
     * @return Change[]
     */
    public function fixed() : array
    {
        $fixed = \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::fixed()), $this->changes())
        );

        \uasort($fixed, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $fixed;
    }

    /**
     * @return Change[]
     */
    public function removed() : array
    {
        $removed =  \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::removed()), $this->changes())
        );

        \uasort($removed, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $removed;
    }

    /**
     * @return Change[]
     */
    public function deprecated() : array
    {
        $deprecated = \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::deprecated()), $this->changes())
        );

        \uasort($deprecated, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $deprecated;
    }

    /**
     * @return Change[]
     */
    public function security() : array
    {
        $security = \array_merge(
            ...\array_map(fn (Changes $changes) => $changes->withType(Type::security()), $this->changes())
        );

        \uasort($security, function (Change $changeA, Change $changeB) : int {
            if ($changeB->source()->date()->isEqual($changeA->source()->date())) {
                return $changeA->source()->description() <=> $changeB->source()->description();
            }

            return $changeB->source()->date()->toDateTimeImmutable() <=> $changeA->source()->date()->toDateTimeImmutable();
        });

        return $security;
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
}

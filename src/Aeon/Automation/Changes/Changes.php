<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

final class Changes
{
    /**
     * @var Change[]
     */
    private array $changes;

    public function __construct(Change ...$changes)
    {
        $types = \array_unique(\array_map(fn (Change $change) : string => $change->source()->type(), $changes));
        $id = \array_unique(\array_map(fn (Change $change) : string => $change->source()->id(), $changes));

        if (\count($types) > 1 || \count($id) > 1) {
            throw new \InvalidArgumentException('All changes must come from the same source and must be the same type');
        }

        if (!\count($changes)) {
            throw new \InvalidArgumentException("Changes can't be empty");
        }

        $this->changes = $changes;
    }

    public function source() : ChangesSource
    {
        return \current($this->changes)->source();
    }

    /**
     * @return Change[]
     */
    public function withType(Type $type) : array
    {
        return \array_values(\array_filter($this->changes, fn (Change $change) : bool => $change->type()->isEqual($type)));
    }

    public function added() : array
    {
        return $this->withType(Type::added());
    }

    public function changed() : array
    {
        return $this->withType(Type::changed());
    }

    public function fixed() : array
    {
        return $this->withType(Type::fixed());
    }

    public function removed() : array
    {
        return $this->withType(Type::removed());
    }

    public function deprecated() : array
    {
        return $this->withType(Type::deprecated());
    }

    public function security() : array
    {
        return $this->withType(Type::security());
    }

    /**
     * @return Change[]
     */
    public function all() : array
    {
        return $this->changes;
    }

    public function count() : int
    {
        return \count($this->all());
    }

    public function merge(self $changes) : self
    {
        return new self(...\array_merge($this->changes, $changes->changes));
    }

    /**
     * @psalm-param callable(Change $change) : self $callable
     *
     * @psalm-return self
     */
    public function map(callable $callable) : self
    {
        return new self(...\array_map($callable, $this->changes));
    }
}

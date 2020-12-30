<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Type;

final class Changes
{
    private ChangesSource $source;

    /**
     * @var Change[]
     */
    private array $changes;

    public function __construct(ChangesSource $source, Change ...$changes)
    {
        $this->source = $source;
        $this->changes = $changes;
    }

    public function empty() : bool
    {
        return \count($this->changes) === 0;
    }

    public function source() : ChangesSource
    {
        return $this->source;
    }

    /**
     * @return Change[]
     */
    public function withType(Type $type) : array
    {
        return \array_values(\array_filter($this->changes, fn (Change $change) : bool => $change->type()->isEqual($type)));
    }

    /**
     * @return Change[]
     */
    public function all() : array
    {
        return $this->changes;
    }
}

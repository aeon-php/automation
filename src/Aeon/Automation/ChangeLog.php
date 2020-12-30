<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Aeon\Calendar\Gregorian\Day;

final class ChangeLog
{
    private string $release;

    private Day $day;

    /**
     * @var Changes[]
     */
    private array $changes;

    public function __construct(string $release, Day $day)
    {
        $this->release = $release;
        $this->day = $day;
        $this->changes = [];
    }

    public function release() : string
    {
        return $this->release;
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

    public function add(Changes $changes) : void
    {
        foreach ($this->changes as $change) {
            if ($change->source()->equals($changes->source())) {
                return;
            }
        }

        $this->changes[] = $changes;
    }
}

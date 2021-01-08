<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\GitHub\Commit;

final class Scope
{
    private ?Commit $commitStart;

    private ?Commit $commitEnd;

    public function __construct(?Commit $commitStart = null, ?Commit $commitEnd = null)
    {
        $this->commitStart = $commitStart;
        $this->commitEnd = $commitEnd;
    }

    public function override(self $scope) : self
    {
        return new self(
            $scope->commitStart() ? $scope->commitStart() : $this->commitStart,
            $scope->commitEnd() ? $scope->commitEnd() : $this->commitEnd,
        );
    }

    public function commitStart() : ?Commit
    {
        return $this->commitStart;
    }

    public function commitEnd() : ?Commit
    {
        return $this->commitEnd;
    }

    public function reverse() : self
    {
        return new self($this->commitEnd, $this->commitStart);
    }

    public function isFull() : bool
    {
        return $this->commitStart !== null && $this->commitEnd !== null;
    }

    public function isEmpty() : bool
    {
        return $this->commitStart === null && $this->commitEnd === null;
    }
}

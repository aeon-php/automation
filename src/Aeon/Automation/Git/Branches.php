<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

final class Branches
{
    /**
     * @var Branch[]
     */
    private array $branches;

    public function __construct(Branch ...$branches)
    {
        $this->branches = $branches;
    }

    /**
     * @return Branch[]
     */
    public function all()
    {
        return $this->branches;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

final class Change
{
    private Type $type;

    private string $description;

    public function __construct(Type $type, string $description)
    {
        $this->type = $type;
        $this->description = $description;
    }

    public function type() : Type
    {
        return $this->type;
    }

    public function description() : string
    {
        return $this->description;
    }
}

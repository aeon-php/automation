<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

use Aeon\Automation\ChangesSource;

final class Change
{
    private ChangesSource $source;

    private Type $type;

    private string $description;

    public function __construct(ChangesSource $source, Type $type, string $description)
    {
        $this->source = $source;
        $this->type = $type;
        $this->description = $description;
    }

    public function source() : ChangesSource
    {
        return $this->source;
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

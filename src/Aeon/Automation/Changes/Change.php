<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

final class Change
{
    private ChangesSource $source;

    private Type $type;

    private string $description;

    /**
     * The reason for change to not use source description/title is that multiple changes might come from single source.
     * For example when parsing HTML content of Pull Request.
     */
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

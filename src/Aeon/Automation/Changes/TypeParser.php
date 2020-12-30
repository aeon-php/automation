<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

interface TypeParser
{
    public function parse(string $message) : Change;
}

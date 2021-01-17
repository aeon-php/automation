<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog;

use Aeon\Automation\Releases;

interface Source
{
    public function releases() : Releases;
}

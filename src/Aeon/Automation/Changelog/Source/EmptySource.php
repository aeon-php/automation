<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog\Source;

use Aeon\Automation\Changelog\Source;
use Aeon\Automation\Releases;

final class EmptySource implements Source
{
    public function releases() : Releases
    {
        return new Releases();
    }
}

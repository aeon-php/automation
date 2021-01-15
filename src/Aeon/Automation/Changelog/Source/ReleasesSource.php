<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog\Source;

use Aeon\Automation\Changelog\Source;
use Aeon\Automation\Releases;

final class ReleasesSource implements Source
{
    /**
     * @var Releases
     */
    private Releases $releases;

    public function __construct(Releases $releases)
    {
        $this->releases = $releases;
    }

    /**
     * @return Releases
     */
    public function releases() : Releases
    {
        return $this->releases;
    }
}

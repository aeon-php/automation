<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit;

use Aeon\Automation\Release;
use Aeon\Automation\Releases;
use Aeon\Calendar\Gregorian\Day;

final class ReleasesTest
{
    public function test_sort_releases() : void
    {
        $releases = (new Releases(
            new Release('5.0.0', Day::fromString('2021-01-09')),
            new Release('3.3.6', Day::fromString('2021-01-03')),
            new Release('4.1.0', Day::fromString('2021-01-06')),
            new Release('Unreleased', Day::fromString('2021-01-10')),
        ))->sortDateDesc();
    }
}

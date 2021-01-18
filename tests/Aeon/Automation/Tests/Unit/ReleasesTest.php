<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit;

use Aeon\Automation\Release;
use Aeon\Automation\Releases;
use Aeon\Calendar\Gregorian\Day;
use PHPUnit\Framework\TestCase;

final class ReleasesTest extends TestCase
{
    public function test_sort_releases() : void
    {
        $releases = (new Releases(
            new Release('5.0.0', Day::fromString('2021-01-09')),
            new Release('3.3.6', Day::fromString('2021-01-03')),
            new Release('4.1.0', Day::fromString('2021-01-06')),
            new Release('Unreleased', Day::fromString('2021-01-10')),
        ))->sort();

        $this->assertSame(['Unreleased', '5.0.0', '4.1.0', '3.3.6'], \array_map(fn (Release $release) : string => $release->name(), $releases->all()));
    }

    public function test_sort_releases_not_semver_releases() : void
    {
        $releases = (new Releases(
            new Release('Release-A', Day::fromString('2021-01-09')),
            new Release('Release-B', Day::fromString('2021-01-03')),
            new Release('Release-C', Day::fromString('2021-01-06')),
            new Release('Unreleased', Day::fromString('2021-01-10')),
        ))->sort();

        $this->assertSame(['Unreleased', 'Release-A', 'Release-C', 'Release-B'], \array_map(fn (Release $release) : string => $release->name(), $releases->all()));
    }

    public function test_sort_releases_with_the_same_date() : void
    {
        $releases = (new Releases(
            new Release('5.0.0', Day::fromString('2021-01-09')),
            new Release('3.3.6', Day::fromString('2021-01-09')),
            new Release('4.1.0', Day::fromString('2021-01-09')),
            new Release('Unreleased', Day::fromString('2021-01-09')),
        ))->sort();

        $this->assertSame(['Unreleased', '5.0.0', '4.1.0', '3.3.6'], \array_map(fn (Release $release) : string => $release->name(), $releases->all()));
    }

    public function test_sort_releases_with_the_same_date_and_unreleased_first() : void
    {
        $releases = (new Releases(
            new Release('Unreleased', Day::fromString('2021-01-09')),
            new Release('5.0.0', Day::fromString('2021-01-09')),
            new Release('3.3.6', Day::fromString('2021-01-09')),
            new Release('4.1.0', Day::fromString('2021-01-09')),
        ))->sort();

        $this->assertSame(['Unreleased', '5.0.0', '4.1.0', '3.3.6'], \array_map(fn (Release $release) : string => $release->name(), $releases->all()));
    }

    public function test_sort_releases_with_the_same_date_but_not_semver() : void
    {
        $releases = (new Releases(
            new Release('Release-A', Day::fromString('2021-01-09')),
            new Release('Release-C', Day::fromString('2021-01-09')),
            new Release('Release-B', Day::fromString('2021-01-09')),
            new Release('Unreleased', Day::fromString('2021-01-09')),
        ))->sort();

        $this->assertSame(['Unreleased', 'Release-A', 'Release-C', 'Release-B'], \array_map(fn (Release $release) : string => $release->name(), $releases->all()));
    }
}

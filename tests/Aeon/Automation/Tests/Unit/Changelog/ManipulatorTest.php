<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changelog;

use Aeon\Automation\Changelog\Manipulator;
use Aeon\Automation\Changelog\Source\ReleasesSource;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Release;
use Aeon\Automation\Releases;
use Aeon\Automation\Tests\Mother\Changes\ChangeMother;
use Aeon\Automation\Tests\Mother\GitHub\SHAMother;
use Aeon\Calendar\Gregorian\Day;
use Monolog\Test\TestCase;

final class ManipulatorTest extends TestCase
{
    public function test_update_empty_release() : void
    {
        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(1, 'Update README.md', 'norberttech')
            )
        );

        $releases = (new Manipulator())->update(
            new ReleasesSource(new Releases()),
            $release
        );

        $this->assertSame(1, $releases->count());
    }

    public function test_update_with_unreleased_when_other_release_exists() : void
    {
        $release = new Release('0.1.0', Day::fromString('2021-01-4'));
        $release->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Changed Something', 'norberttech'),
            )
        );

        $unreleased = new Release('Unreleased', Day::fromString('2021-01-5'));
        $unreleased->add(
            new Changes(
                ChangeMother::pullRequestChanged(2, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(2, 'Update README.md', 'norberttech')
            )
        );

        $releases = (new Manipulator())->update(
            new ReleasesSource(new Releases($release)),
            $unreleased
        )->sort();

        $this->assertSame(2, $releases->count());
        $this->assertSame('Unreleased', $releases->all()[0]->name());
        $this->assertSame('0.1.0', $releases->all()[1]->name());
    }

    public function test_update_existing_release() : void
    {
        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(1, 'Update README.md', 'norberttech')
            )
        );

        $updatedRelease = new Release('Unreleased', Day::fromString('2021-01-4'));
        $updatedRelease->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(1, 'Update README.md', 'norberttech')
            )
        );
        $updatedRelease->add(
            new Changes(
                ChangeMother::commitChanged(SHAMother::random(), 'Update index.php', 'norberttech')
            )
        );

        $releases = (new Manipulator())->update(
            new ReleasesSource(new Releases($release)),
            $updatedRelease
        );

        $this->assertSame(1, $releases->count());
        $this->assertSame(3, \count($releases->all()[0]->changed()));
    }

    public function test_release_unreleased() : void
    {
        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(1, 'Update README.md', 'norberttech')
            )
        );

        $releases = (new Manipulator())->release(
            new ReleasesSource(new Releases($release)),
            '0.1.0',
            Day::fromString('2021-05-01')
        );

        $this->assertSame(1, $releases->count());
        $this->assertSame('0.1.0', $releases->all()[0]->name());
        $this->assertSame('2021-05-01', $releases->all()[0]->day()->toString());
        $this->assertSame(2, \count($releases->all()[0]->changed()));
    }

    public function test_release_without_unreleased() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is nothing to release');

        $release = new Release('0.1.0', Day::fromString('2021-01-4'));
        $release->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(1, 'Update README.md', 'norberttech')
            )
        );

        (new Manipulator())->release(
            new ReleasesSource(new Releases($release)),
            '0.1.0',
            Day::fromString('2021-05-01')
        );
    }

    public function test_release_of_something_that_was_already_released() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Release 0.1.0 already exists and can't be released again.");

        $releaseUnreleased = new Release('Unreleased', Day::fromString('2021-01-5'));
        $releaseUnreleased->add(
            new Changes(
                ChangeMother::pullRequestChanged(2, 'Update CHANGELOG.md', 'norberttech'),
            )
        );

        $release010 = new Release('0.1.0', Day::fromString('2021-01-4'));
        $release010->add(
            new Changes(
                ChangeMother::pullRequestChanged(1, 'Update CHANGELOG.md', 'norberttech'),
                ChangeMother::pullRequestChanged(1, 'Update README.md', 'norberttech')
            )
        );

        (new Manipulator())->release(
            new ReleasesSource(new Releases($releaseUnreleased, $release010)),
            '0.1.0',
            Day::fromString('2021-05-01')
        );
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit;

use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Release;
use Aeon\Automation\Tests\Mother\Changes\ChangeMother;
use Aeon\Automation\Tests\Mother\GitHub\SHAMother;
use Aeon\Calendar\Gregorian\Day;
use PHPUnit\Framework\TestCase;

final class ReleaseTest extends TestCase
{
    public function test_that_two_identical_releases_are_equal() : void
    {
        $release1 = new Release('0.0.1', Day::fromString('2021-01-09'));
        $release2 = new Release('0.0.1', Day::fromString('2021-01-09'));

        $release1->add($changes1 = new Changes(ChangeMother::commitChanged(SHAMother::random(), 'change 1', 'user')));
        $release1->add($changes2 = new Changes(ChangeMother::commitChanged(SHAMother::random(), 'change 2', 'user')));

        $release2->add($changes2);
        $release2->add($changes1);

        $this->assertTrue($release1->isEqual($release2));
        $this->assertTrue($release2->isEqual($release1));
    }

    public function test_that_date_are_irrelevant() : void
    {
        $release1 = new Release('0.0.1', Day::fromString('2021-01-19'));
        $release2 = new Release('0.0.1', Day::fromString('2021-01-09'));

        $release1->add($changes1 = new Changes(ChangeMother::commitChanged(SHAMother::random(), 'change 1', 'user')));
        $release1->add($changes2 = new Changes(ChangeMother::commitChanged(SHAMother::random(), 'change 2', 'user')));

        $release2->add($changes2);
        $release2->add($changes1);

        $this->assertTrue($release1->isEqual($release2));
        $this->assertTrue($release2->isEqual($release1));
    }

    public function test_that_two_releases_with_different_changes_are_not_equal() : void
    {
        $release1 = new Release('0.0.1', Day::fromString('2021-01-09'));
        $release2 = new Release('0.0.1', Day::fromString('2021-01-09'));

        $release1->add($changes1 = new Changes(ChangeMother::commitChanged(SHAMother::random(), 'change 1', 'user')));
        $release1->add($changes2 = new Changes(ChangeMother::commitChanged(SHAMother::random(), 'change 2', 'user')));

        $release2->add($changes2);

        $this->assertFalse($release1->isEqual($release2));
        $this->assertFalse($release2->isEqual($release1));
    }
}

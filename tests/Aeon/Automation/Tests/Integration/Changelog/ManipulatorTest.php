<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Changelog;

use Aeon\Automation\Changelog\Manipulator;
use Aeon\Automation\Changelog\Source\MarkdownSource;
use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Configuration;
use Aeon\Automation\Release;
use Aeon\Calendar\Gregorian\DateTime;
use Aeon\Calendar\Gregorian\Day;
use Monolog\Test\TestCase;

final class ManipulatorTest extends TestCase
{
    public function test_add_new_changed_to_unreleased_release() : void
    {
        $factory = new Release\FormatterFactory(new Configuration(\getenv('AUTOMATION_ROOT_DIR'), []));
        $formatter = $factory->create('markdown', 'keepachangelog');

        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_COMMIT,
                    'aae10af449f10def5edda9f617760daf9d52cb6a',
                    'https://github.com/aeon-php/calendar/commit/aae10af449f10def5edda9f617760daf9d52cb6a',
                    'Update CHANGELOG.md',
                    'Update CHANGELOG.md',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::changed(),
                'Update CHANGELOG.md'
            )
        ));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '88',
                    'https://github.com/aeon-php/calendar/pull/88',
                    'Year::fromString method',
                    'Year::fromString method',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::fixed(),
                'Year::fromString method'
            )
        ));

        $markdown = $formatter->formatRelease($release);

        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '89',
                    'https://github.com/aeon-php/calendar/pull/89',
                    'Added something very cool',
                    'Added something very cool',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::added(),
                'Added something very cool'
            )
        ));

        $manipulator = new Manipulator();
        $releases = $manipulator->update(new MarkdownSource($markdown), $release);

        $this->assertSame(1, $releases->count());
        $this->assertSame(1, \count($releases->all()[0]->changed()));
        $this->assertSame(1, \count($releases->all()[0]->fixed()));
        $this->assertSame(1, \count($releases->all()[0]->added()));
        $this->assertSame('89', $releases->all()[0]->added()[0]->source()->id());
    }
}

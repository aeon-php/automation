<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Release;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Configuration;
use Aeon\Automation\Release;
use Aeon\Automation\Release\FormatterFactory;
use Aeon\Automation\Releases;
use Aeon\Calendar\Gregorian\DateTime;
use Aeon\Calendar\Gregorian\Day;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FormatterTest extends TestCase
{
    public function test_markdown_keep_a_changelog_release() : void
    {
        $factory = new FormatterFactory(new Configuration(new NullLogger(), \getenv('AUTOMATION_ROOT_DIR'), []));
        $formatter = $factory->create('markdown', 'keepachangelog');

        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '88',
                    'https://github.com/aeon-php/calendar/pull/88',
                    'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern',
                    '',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::fixed(),
                'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern'
            )
        ));

        $this->assertSame(
            <<<'MARKDOWN'
## [Unreleased] - 2021-01-04

### Fixed
- [#88](https://github.com/aeon-php/calendar/pull/88) - **include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern** - [@norberttech](https://github.com/norberttech)

Generated by [Automation](https://github.com/aeon-php/automation)
MARKDOWN,
            $formatter->formatRelease($release)
        );
    }

    public function test_markdown_classic_release() : void
    {
        $factory = new FormatterFactory(new Configuration(new NullLogger(), \getenv('AUTOMATION_ROOT_DIR'), []));
        $formatter = $factory->create('markdown', 'classic');

        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '88',
                    'https://github.com/aeon-php/calendar/pull/88',
                    'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern',
                    '',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::fixed(),
                'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern'
            )
        ));

        $this->assertSame(
            <<<'MARKDOWN'
## [Unreleased] - 2021-01-04

- [#88](https://github.com/aeon-php/calendar/pull/88) - **include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern** - [@norberttech](https://github.com/norberttech)

Generated by [Automation](https://github.com/aeon-php/automation)
MARKDOWN,
            $formatter->formatRelease($release)
        );
    }

    public function test_html_keep_a_changelog_release() : void
    {
        $factory = new FormatterFactory(new Configuration(new NullLogger(), \getenv('AUTOMATION_ROOT_DIR'), []));
        $formatter = $factory->create('html', 'keepachangelog');

        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '88',
                    'https://github.com/aeon-php/calendar/pull/88',
                    'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern',
                    '',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::fixed(),
                'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern'
            )
        ));

        $this->assertSame(
            <<<'HTML'
<h2>[Unreleased] - 2021-01-04</h2>

<h3>Fixed</h3>
<ul>
  <li><a href="https://github.com/aeon-php/calendar/pull/88">#88</a> - include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern - <a href="https://github.com/norberttech">@norberttech</a></li>
</ul>

<footer>Generated by <a href="https://github.com/aeon-php/automation">Automation</a></footer>
HTML,
            $formatter->formatRelease($release)
        );
    }

    public function test_html_classic_release() : void
    {
        $factory = new FormatterFactory(new Configuration(new NullLogger(), \getenv('AUTOMATION_ROOT_DIR'), []));
        $formatter = $factory->create('html', 'classic');

        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '88',
                    'https://github.com/aeon-php/calendar/pull/88',
                    'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern',
                    '',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::fixed(),
                'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern'
            )
        ));

        $this->assertSame(
            <<<'HTML'
<h2>[Unreleased] - 2021-01-04</h2>

<ul>
  <li><a href="https://github.com/aeon-php/calendar/pull/88">#88</a> - include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern - <a href="https://github.com/norberttech">@norberttech</a></li>
</ul>

<footer>Generated by <a href="https://github.com/aeon-php/automation">Automation</a></footer>
HTML,
            $formatter->formatRelease($release)
        );
    }

    public function test_html_classic_releases() : void
    {
        $factory = new FormatterFactory(new Configuration(new NullLogger(), \getenv('AUTOMATION_ROOT_DIR'), []));
        $formatter = $factory->create('html', 'classic');

        $release = new Release('Unreleased', Day::fromString('2021-01-4'));
        $release->add(new Changes(
            new Change(
                new ChangesSource(
                    ChangesSource::TYPE_PULL_REQUEST,
                    '88',
                    'https://github.com/aeon-php/calendar/pull/88',
                    'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern',
                    '',
                    DateTime::fromString('2021-01-01'),
                    'norberttech',
                    'https://github.com/norberttech'
                ),
                Type::fixed(),
                'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern'
            )
        ));

        $releases = new Releases($release);

        $this->assertSame(
            <<<'HTML'
<h2>[Unreleased] - 2021-01-04</h2>

<ul>
  <li><a href="https://github.com/aeon-php/calendar/pull/88">#88</a> - include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern - <a href="https://github.com/norberttech">@norberttech</a></li>
</ul>

<footer>Generated by <a href="https://github.com/aeon-php/automation">Automation</a></footer>
HTML,
            $formatter->formatReleases($releases)
        );
    }
}

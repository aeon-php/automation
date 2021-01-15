<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changelog\Source;

use Aeon\Automation\Changelog\Source\MarkdownSource;
use PHPUnit\Framework\TestCase;

final class MarkdownSourceTest extends TestCase
{
    public function test_valid_markdown_with_one_release() : void
    {
        $source = new MarkdownSource(
            <<<'MARKDOWN'
## [0.13.3] - 2021-01-04

### Changed
  - [aae10a](https://github.com/aeon-php/calendar/commit/aae10af449f10def5edda9f617760daf9d52cb6a) - **Update CHANGELOG.md** - [@norberttech](https://github.com/norberttech)
  - [#87](https://github.com/aeon-php/calendar/pull/87) - **Extended leap seconds list expiration date** - [@norberttech](https://github.com/norberttech)
  - [c7c422](https://github.com/aeon-php/calendar/commit/c7c422385e36d3d41b3b18976f6780f34dc6af0b) - **Small syntax fix in CHANGELOG.md file** - [@norberttech](https://github.com/norberttech)
  - [ee80b6](https://github.com/aeon-php/calendar/commit/ee80b6d8d2371d2560fa2b3c38a9fd50a53c0b86) - **version 0.1.0 changelog** - [@norberttech](https://github.com/norberttech)

### Fixed
  - [#88](https://github.com/aeon-php/calendar/pull/88) - **Year::fromString method** - [@norberttech](https://github.com/norberttech)
MARKDOWN
        );

        $this->assertSame(1, $source->releases()->count());
        $this->assertSame('0.13.3', $source->releases()->all()[0]->name());
        $this->assertSame(4, \count($source->releases()->all()[0]->changed()));
        $this->assertSame(1, \count($source->releases()->all()[0]->fixed()));
        $this->assertSame(0, \count($source->releases()->all()[0]->added()));
        $this->assertSame(0, \count($source->releases()->all()[0]->security()));
        $this->assertSame(0, \count($source->releases()->all()[0]->deprecated()));

        $this->assertSame('aae10a', $source->releases()->all()[0]->changed()[0]->source()->id());
        $this->assertSame('87', $source->releases()->all()[0]->changed()[1]->source()->id());
        $this->assertSame('c7c422', $source->releases()->all()[0]->changed()[2]->source()->id());
        $this->assertSame('ee80b6', $source->releases()->all()[0]->changed()[3]->source()->id());

        $this->assertSame('https://github.com/aeon-php/calendar/commit/aae10af449f10def5edda9f617760daf9d52cb6a', $source->releases()->all()[0]->changed()[0]->source()->url());
        $this->assertSame('Update CHANGELOG.md', $source->releases()->all()[0]->changed()[0]->source()->description());
        $this->assertSame('Update CHANGELOG.md', $source->releases()->all()[0]->changed()[0]->source()->title());
        $this->assertSame('norberttech', $source->releases()->all()[0]->changed()[0]->source()->user());
        $this->assertSame('https://github.com/norberttech', $source->releases()->all()[0]->changed()[0]->source()->userUrl());
    }

    public function test_valid_markdown_with_multiple_releases() : void
    {
        $source = new MarkdownSource(
            <<<'MARKDOWN'
## [Unreleased] - 2020-12-13
### Changed
 - [1f32aa](https://github.com/coduo/php-matcher/commit/1f32aa7c5e5dee773710aed1c2485610eedacbee) - **Update composer.json** - [@norberttech](https://github.com/norberttech)
 - [54594c](https://github.com/coduo/php-matcher/commit/54594c2874f12579b12dcc346de9d86a5c89fd23) - **Updated phar dependencies, increased timeout for infection, changed default branch for scheduled jobs** - [@norberttech](https://github.com/norberttech)
 - [e3eab6](https://github.com/coduo/php-matcher/commit/e3eab6a6cf57c849d436d38bf79f5a0040f16491) - **Update README.md** - [@norberttech](https://github.com/norberttech)
 - [#215](https://github.com/coduo/php-matcher/pull/215) - **PHP 8.0 compability.** - [@dotdevru](https://github.com/dotdevru)

## [5.0.0] - 2020-09-27
### Changed
 - [6d54bc](https://github.com/coduo/php-matcher/commit/6d54bc01ad882774fb04bf6d4e77a9b71838e1e7) - **Fixed version triggering mutation tests in github workflow** - [@norberttech](https://github.com/norberttech)
 - [#209](https://github.com/coduo/php-matcher/pull/209) - **Fixed issue with false positive detection of valid json string** - [@norberttech](https://github.com/norberttech)
 - [48f176](https://github.com/coduo/php-matcher/commit/48f17677120617a450c62b6611f695e8c23c32bb) - **Removed tools from repository** - [@norberttech](https://github.com/norberttech)
 - [f5520d](https://github.com/coduo/php-matcher/commit/f5520d9e63222a4ff2c120a9b44042f9d5edea49) - **Update README.md** - [@norberttech](https://github.com/norberttech)
 - [f1be44](https://github.com/coduo/php-matcher/commit/f1be448427f1afc2f4c2dbbb281b2268e61d645f) - **Improved CS rules** - [@norberttech](https://github.com/norberttech)
 - [b4571b](https://github.com/coduo/php-matcher/commit/b4571b52bef912cce1a373418d20d475b97141d9) - **Update README.md** - [@norberttech](https://github.com/norberttech)
 - [#205](https://github.com/coduo/php-matcher/pull/205) - **Make Backtrace optional** - [@norberttech](https://github.com/norberttech)
 - [#204](https://github.com/coduo/php-matcher/pull/204) - **Remove xml, expression and property accessor components from core dependencies** - [@norberttech](https://github.com/norberttech)
 - [6803f9](https://github.com/coduo/php-matcher/commit/6803f9938f2695e75ba03262ada8fb48d7e67693) - **Added gitattributes** - [@norberttech](https://github.com/norberttech)
 - [a1e3ea](https://github.com/coduo/php-matcher/commit/a1e3ea0263505b3be152c239f2d3bb6580ef2299) - **Updated README** - [@norberttech](https://github.com/norberttech)

## [4.0.0] - 2019-12-22
### Changed
 - [aaa336](https://github.com/coduo/php-matcher/commit/aaa33639fe03d5883e0ccfbeea3d02a65ebc567c) - **Bumped dev-master alias to 5.0-dev** - [@norberttech](https://github.com/norberttech)
 - [#193](https://github.com/coduo/php-matcher/pull/193) - **Fixed issue where unbound key matching would not validate keys in pat…** - [@raing3](https://github.com/raing3)
 - [267b67](https://github.com/coduo/php-matcher/commit/267b67a152359ecd0aaf01851248a7ca7214794b) - **Update README.md** - [@norberttech](https://github.com/norberttech)
 - [#186](https://github.com/coduo/php-matcher/pull/186) - **Replaced PHPMatcher facade with implementation that makes possible to access backtrace** - [@norberttech](https://github.com/norberttech)
 - [#190](https://github.com/coduo/php-matcher/pull/190) - **README.md - fix example in "Json matching with unbounded arrays and objects" (master)** - [@domis86](https://github.com/domis86)
 - [#185](https://github.com/coduo/php-matcher/pull/185) - **added HasProperty pattern expander** - [@norberttech](https://github.com/norberttech)
 - [#184](https://github.com/coduo/php-matcher/pull/184) - **Upgraded coduo/php-to-string dependency** - [@norberttech](https://github.com/norberttech)
 - [#182](https://github.com/coduo/php-matcher/pull/182) - **Upgrades on Symfony components** - [@ianrodrigues](https://github.com/ianrodrigues)

## [3.2.2] - 2019-08-11
### Changed
 - [#167](https://github.com/coduo/php-matcher/pull/167) - **JSON full text matching fixed** - [@norberttech](https://github.com/norberttech)
MARKDOWN
        );

        $this->assertSame(4, $source->releases()->count());
        $this->assertSame('Unreleased', $source->releases()->all()[0]->name());
        $this->assertSame('5.0.0', $source->releases()->all()[1]->name());
        $this->assertSame('4.0.0', $source->releases()->all()[2]->name());
        $this->assertSame('3.2.2', $source->releases()->all()[3]->name());
    }

    public function test_invalid_markdown() : void
    {
        $source = new MarkdownSource(
            <<<'MARKDOWN'
this is just a simple text
MARKDOWN
        );

        $this->assertSame(0, $source->releases()->count());
    }
}

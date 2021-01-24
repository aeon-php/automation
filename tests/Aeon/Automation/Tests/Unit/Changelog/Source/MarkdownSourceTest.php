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

    public function test_merge_changes_from_the_same_source() : void
    {
        $source = new MarkdownSource(
            <<<'MARKDOWN'
## [Unreleased] - 2021-01-02

### Added
  - [#6](https://github.com/aeon-php/automation/pull/6) - **support for generating changelogs from tags that are diverged** - [@norberttech](https://github.com/norberttech)
  - [#6](https://github.com/aeon-php/automation/pull/6) - **twig template for changelog generation** - [@norberttech](https://github.com/norberttech)
  - [#6](https://github.com/aeon-php/automation/pull/6) - **--changed-before option to changelog:generate command** - [@norberttech](https://github.com/norberttech)
  - [#6](https://github.com/aeon-php/automation/pull/6) - **--with-commit to tag:list command** - [@norberttech](https://github.com/norberttech)
  - [#5](https://github.com/aeon-php/automation/pull/5) - **PrefixParser to detect change type from change title prefix** - [@norberttech](https://github.com/norberttech)
  - [#5](https://github.com/aeon-php/automation/pull/5) - **`Change::name() : string` and `Change::all() : array`  methods** - [@norberttech](https://github.com/norberttech)
  - [#4](https://github.com/aeon-php/automation/pull/4) - **Make first character of change title uppercase** - [@norberttech](https://github.com/norberttech)
  - [#3](https://github.com/aeon-php/automation/pull/3) - **support for conventional commit format** - [@norberttech](https://github.com/norberttech)
  - [#3](https://github.com/aeon-php/automation/pull/3) - **Change object that holds Type and description** - [@norberttech](https://github.com/norberttech)
  - [#2](https://github.com/aeon-php/automation/pull/2) - **static analyze github workflow** - [@norberttech](https://github.com/norberttech)
  - [#1](https://github.com/aeon-php/automation/pull/1) - **`change-log:get --commit-end` flag that takes sha of a commit that should be the last in changelog** - [@norberttech](https://github.com/norberttech)
  - [#1](https://github.com/aeon-php/automation/pull/1) - **Github tests workflow** - [@norberttech](https://github.com/norberttech)
  - [#1](https://github.com/aeon-php/automation/pull/1) - **Dependabot configuration** - [@norberttech](https://github.com/norberttech)
  - [b3a906](https://github.com/aeon-php/automation/commit/b3a906801897f72c3e88f696aa99e9dc7b88005f) - **basic README** - [@norberttech](https://github.com/norberttech)
  - [e5849d](https://github.com/aeon-php/automation/commit/e5849da3147caaf1394cfc149fdc405589d818ec) - **initial changelog** - [@norberttech](https://github.com/norberttech)

### Changed
  - [#6](https://github.com/aeon-php/automation/pull/6) - **change-log:get command into changelog:generate** - [@norberttech](https://github.com/norberttech)
  - [#6](https://github.com/aeon-php/automation/pull/6) - **--tag-start &amp; --tag-end into --tag in changelog:generate commad** - [@norberttech](https://github.com/norberttech)
  - [ede6eb](https://github.com/aeon-php/automation/commit/ede6eb897f8bd0ba77ceedb3fc3ccb44590124a2) - **Update CHANGELOG.md** - [@norberttech](https://github.com/norberttech)
  - [#5](https://github.com/aeon-php/automation/pull/5) - **Replaced redundant methods in Changes collection with more generic ones** - [@norberttech](https://github.com/norberttech)
  - [#3](https://github.com/aeon-php/automation/pull/3) - **changes are now parsed by ChangesParser object, not directly in PullRequest/Commit** - [@norberttech](https://github.com/norberttech)
  - [#1](https://github.com/aeon-php/automation/pull/1) - **the way to access pull requests related to the commit, those are now taken from the `Commit` object** - [@norberttech](https://github.com/norberttech)
  - [6ea7ef](https://github.com/aeon-php/automation/commit/6ea7ef4eca73bccbaaab23f26a365f83b3586613) - **automation readme** - [@norberttech](https://github.com/norberttech)
  - [61b146](https://github.com/aeon-php/automation/commit/61b146ba1360436136c7dc9e57d7658b3d6da810) - **automation xsd** - [@norberttech](https://github.com/norberttech)
  - [ea9540](https://github.com/aeon-php/automation/commit/ea9540a5f4399ee4a70e8e2f4df8ef0467fbe42e) - **Improved command descriptions** - [@norberttech](https://github.com/norberttech)
  - [885e50](https://github.com/aeon-php/automation/commit/885e50c5c4e5e0a757c247d65cf4b4576ce168e3) - **Take format from option in change-log:get command** - [@norberttech](https://github.com/norberttech)
  - [933675](https://github.com/aeon-php/automation/commit/93367510905d645a23dc2d86cc2ab9bf4e203e9c) - **Improved support for -v|-vv|-vvv** - [@norberttech](https://github.com/norberttech)
  - [cf304f](https://github.com/aeon-php/automation/commit/cf304f1c0c9a4db74b017ef134d43986039953a5) - **Move initialization of github client into AbstractCommand** - [@norberttech](https://github.com/norberttech)
  - [4e4108](https://github.com/aeon-php/automation/commit/4e41083de4d76dea2fa90abc7d72815d1ab73718) - **Use commits instead of milestones to generate changelog** - [@norberttech](https://github.com/norberttech)
  - [72564b](https://github.com/aeon-php/automation/commit/72564ba0991f280a74428d10fc1dee9b02659b02) - **Initial commit** - [@norberttech](https://github.com/norberttech)

### Fixed
  - [#6](https://github.com/aeon-php/automation/pull/6) - **fetching all tags by using paginator instead of taking just first page from API** - [@norberttech](https://github.com/norberttech)
  - [#4](https://github.com/aeon-php/automation/pull/4) - **Change Log changes are sorted by date** - [@norberttech](https://github.com/norberttech)
  - [#3](https://github.com/aeon-php/automation/pull/3) - **use Repository commit data instead of GitData to make sure commit author login is always available** - [@norberttech](https://github.com/norberttech)
  - [#2](https://github.com/aeon-php/automation/pull/2) - **Tests failing at PHP 8** - [@norberttech](https://github.com/norberttech)
MARKDOWN
        );

        $this->assertCount(15, $source->releases()->all()[0]->added());
        $this->assertCount(14, $source->releases()->all()[0]->changed());
        $this->assertCount(4, $source->releases()->all()[0]->fixed());
    }

    public function test_markdown_characters_in_change_description_support() : void
    {
        $source = new MarkdownSource(
            $input = <<<'MARKDOWN'
## [Unreleased] - 2021-01-02

### Added
  - [#214](https://github.com/coduo/php-matcher/pull/214) - **include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern** - [@mtomala](https://github.com/mtomala)
  - [#78](https://github.com/coduo/php-matcher/pull/78) - **work around bug https://bugs.php.net/bug.php?id=71964** - [@bendavies](https://github.com/bendavies)
  - [#65](https://github.com/coduo/php-matcher/pull/65) - **this is regular text _with markdown_ emphasis** - [@norberttech](https://github.com/norberttech)
  - [#65](https://github.com/coduo/php-matcher/pull/65) - **this is regular text *with markdown* emphasis** - [@norberttech](https://github.com/norberttech)
  - [#65](https://github.com/coduo/php-matcher/pull/65) - **this is regular text [with markdown](#) url** - [@norberttech](https://github.com/norberttech)
  - [#65](https://github.com/coduo/php-matcher/pull/65) - **this is regular text **with markdown** bold** - [@norberttech](https://github.com/norberttech)
  - [#65](https://github.com/coduo/php-matcher/pull/65) - **something & something** - [@norberttech](https://github.com/norberttech)

Generated by [Automation](https://github.com/aeon-php/automation)
MARKDOWN
        );

        $this->assertSAme(
            'include ArrayMatcher in OrMatcher to fix issues with `@null@||@array@` pattern',
            $source->releases()->all()[0]->added()[0]->description()
        );
        $this->assertSAme(
            'work around bug https://bugs.php.net/bug.php?id=71964',
            $source->releases()->all()[0]->added()[1]->description()
        );
    }
}

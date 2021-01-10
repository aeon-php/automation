<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changes\Detector;

use Aeon\Automation\Changes\Detector\PrefixDetector;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Tests\Mother\Changes\ChangesSourceMother;
use PHPUnit\Framework\TestCase;

final class PrefixParserTest extends TestCase
{
    /**
     * @dataProvider messages_without_prefix
     */
    public function test_support_for_invalid_format(string $message) : void
    {
        $this->assertFalse((new PrefixDetector())->support(ChangesSourceMother::withTitle($message)));
    }

    public function messages_without_prefix() : \Generator
    {
        yield ['addingsomething cool'];
        yield ['nothing cool and without prefix'];
    }

    /**
     * @dataProvider messages_with_prefix
     */
    public function test_getting_changes_by_prefix(string $message, Type $expectedType, string $expectedDescription) : void
    {
        $this->assertTrue((new PrefixDetector())->support(ChangesSourceMother::withTitle($message)));
        $this->assertEquals($expectedType, (new PrefixDetector())->detect(ChangesSourceMother::withTitle($message))->all()[0]->type());
        $this->assertEquals($expectedDescription, (new PrefixDetector())->detect(ChangesSourceMother::withTitle($message))->all()[0]->description());
    }

    public function messages_with_prefix() : \Generator
    {
        yield ['added something cool', Type::added(), 'something cool'];
        yield ['AddEd something cool', Type::added(), 'something cool'];
        yield ['adding something cool', Type::added(), 'something cool'];
        yield ['changed so many different things', Type::changed(), 'so many different things'];
        yield ['SecuRity so many different things', Type::security(), 'so many different things'];
    }
}

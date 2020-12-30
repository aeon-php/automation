<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changes\ChangesParser;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\ChangesParser\PrefixParser;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Tests\Mother\ChangesSourceMother;
use PHPUnit\Framework\TestCase;

final class PrefixParserTest extends TestCase
{
    /**
     * @dataProvider messages_without_prefix
     */
    public function test_support_for_invalid_format(string $message) : void
    {
        $this->assertFalse((new PrefixParser())->support(ChangesSourceMother::withTitle($message)));
    }

    public function messages_without_prefix() : \Generator
    {
        yield ['addingsomething cool'];
        yield ['nothing cool and without prefix'];
    }

    /**
     * @dataProvider messages_with_prefix
     */
    public function test_getting_changes_by_prefix(string $message, array $expectedChanges) : void
    {
        $this->assertTrue((new PrefixParser())->support(ChangesSourceMother::withTitle($message)));
        $this->assertEquals($expectedChanges, (new PrefixParser())->parse(ChangesSourceMother::withTitle($message))->all());
    }

    public function messages_with_prefix() : \Generator
    {
        yield ['added something cool', [new Change(Type::added(), 'something cool')]];
        yield ['AddEd something cool', [new Change(Type::added(), 'something cool')]];
        yield ['adding something cool', [new Change(Type::added(), 'something cool')]];
        yield ['changed so many different things', [new Change(Type::changed(), 'so many different things')]];
        yield ['SecuRity so many different things', [new Change(Type::security(), 'so many different things')]];
    }
}

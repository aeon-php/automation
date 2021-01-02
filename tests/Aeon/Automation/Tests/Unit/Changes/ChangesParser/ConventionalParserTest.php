<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changes\ChangesParser;

use Aeon\Automation\Changes\ChangesParser\ConventionalCommitParser;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Tests\Mother\ChangesSourceMother;
use PHPUnit\Framework\TestCase;

final class ConventionalParserTest extends TestCase
{
    /**
     * @dataProvider valid_conventional_commit_messages
     */
    public function test_valid_conventional_commit_messages(string $message, Type $expectedType, string $expectedDescription) : void
    {
        $this->assertEquals(
            $expectedType,
            (new ConventionalCommitParser())->parse(ChangesSourceMother::withContent($message))->all()[0]->type()
        );
        $this->assertEquals(
            $expectedDescription,
            (new ConventionalCommitParser())->parse(ChangesSourceMother::withContent($message))->all()[0]->description()
        );
    }

    public function valid_conventional_commit_messages() : \Generator
    {
        yield ['fix: fixed something', Type::fixed(), 'fixed something'];
        yield ['add: added something', Type::added(), 'added something'];
        yield ['AddeD: added something', Type::added(), 'added something'];
        yield ['rm: removed something', Type::removed(), 'removed something'];
    }
}

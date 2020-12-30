<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changes\ChangesParser;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\ChangesParser\ConventionalCommitParser;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Tests\Mother\ChangesSourceMother;
use PHPUnit\Framework\TestCase;

final class ConventionalParserTest extends TestCase
{
    /**
     * @dataProvider valid_conventional_commit_messages
     */
    public function test_valid_conventional_commit_messages(string $message, array $expectedChanges) : void
    {
        $this->assertEquals($expectedChanges, (new ConventionalCommitParser())->parse(ChangesSourceMother::withContent($message))->all());
    }

    public function valid_conventional_commit_messages() : \Generator
    {
        yield ['fix: fixed something', [new Change(Type::fixed(), 'fixed something')]];
        yield ['add: added something', [new Change(Type::added(), 'added something')]];
        yield ['AddeD: added something', [new Change(Type::added(), 'added something')]];
        yield ['rm: removed something', [new Change(Type::removed(), 'removed something')]];
    }
}

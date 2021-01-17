<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changes\Detector;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Detector\HTMLChangesDetector;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Tests\Mother\Changes\ChangesSourceMother;
use PHPUnit\Framework\TestCase;

final class HTMLChangesParserTest extends TestCase
{
    public function test_support_for_not_valid_html() : void
    {
        $this->assertFalse((new HTMLChangesDetector())->support(ChangesSourceMother::withDescription('not valid html')));
    }

    public function test_support_for_html_changes_with_invalid_format() : void
    {
        $this->assertFalse((new HTMLChangesDetector())->support(ChangesSourceMother::withDescription('<p>not valid html<p/>')));
    }

    public function test_support_valid_html_format() : void
    {
        $content = <<<'HTML'
<div id="change-log">
    <ul id="added">
        <li>added</li>
        </ul>
    <ul id="changed">
        <li>changed</li>
        </ul>
    <ul id="fixed">
        <li>fixed</li>
        </ul>
    <ul id="removed">
        <li>removed</li>
    </ul>
    <ul id="deprecated">
        <li>deprecated</li>
    </ul>
    <ul id="security">
        <li>security</li>
    </ul>
</div>
HTML;

        $changes = (new HTMLChangesDetector())->detect($source = ChangesSourceMother::withDescription($content));

        $this->assertEquals([new Change($source, Type::added(), 'added')], $changes->withType(Type::added()));
        $this->assertEquals([new Change($source, Type::changed(), 'changed')], $changes->withType(Type::changed()));
        $this->assertEquals([new Change($source, Type::fixed(), 'fixed')], $changes->withType(Type::fixed()));
        $this->assertEquals([new Change($source, Type::removed(), 'removed')], $changes->withType(Type::removed()));
        $this->assertEquals([new Change($source, Type::deprecated(), 'deprecated')], $changes->withType(Type::deprecated()));
        $this->assertEquals([new Change($source, Type::security(), 'security')], $changes->withType(Type::security()));
    }
}

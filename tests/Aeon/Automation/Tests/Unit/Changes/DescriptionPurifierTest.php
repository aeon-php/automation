<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changes;

use Aeon\Automation\Changes\DescriptionPurifier;
use PHPUnit\Framework\TestCase;

final class DescriptionPurifierTest extends TestCase
{
    public function test_html_purification() : void
    {
        $purifier = new DescriptionPurifier();

        $this->assertSame('there is no html here', $purifier->purify('<b>there </b><code>is no html here</code>'));
    }

    public function test_markdown_purification() : void
    {
        $purifier = new DescriptionPurifier();

        $this->assertSame('This should be header', $purifier->purify('### This should be header'));
    }

    public function test_markdown_code_purification() : void
    {
        $purifier = new DescriptionPurifier();

        $this->assertSame('this is `code` exception', $purifier->purify('this is `code` exception'));
    }

    public function test_markdown_asterix_purification() : void
    {
        $purifier = new DescriptionPurifier();

        $this->assertSame('2.0.* - 1.0.*', $purifier->purify('2.0.* - 1.0.*'));
    }
}

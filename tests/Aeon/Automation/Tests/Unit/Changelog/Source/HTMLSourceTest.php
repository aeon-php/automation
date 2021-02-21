<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Unit\Changelog\Source;

use Aeon\Automation\Changelog\Source\HTMLSource;
use PHPUnit\Framework\TestCase;

final class HTMLSourceTest extends TestCase
{
    public function test_html_source_for_empty_file() : void
    {
        $source = new HTMLSource($input = '');

        $this->assertSame(0, $source->releases()->count());
    }
}

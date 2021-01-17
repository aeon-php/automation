<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog\Source;

use Aeon\Automation\Changelog\Source;
use Aeon\Automation\Releases;

final class MarkdownSource implements Source
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function releases() : Releases
    {
        return (new HTMLSource((new \Parsedown())->parse($this->content)))->releases();
    }
}

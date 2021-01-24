<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog\Source;

use Aeon\Automation\Changelog\Source;
use Aeon\Automation\Releases;
use League\CommonMark\CommonMarkConverter;

final class MarkdownSource implements Source
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function releases() : Releases
    {
        $converter = new CommonMarkConverter([
            'enable_em' => false,
        ]);

        return (new HTMLSource($converter->convertToHtml(\str_replace('`', '\`', $this->content))))->releases();
    }
}

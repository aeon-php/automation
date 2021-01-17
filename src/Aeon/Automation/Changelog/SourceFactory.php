<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog;

use Aeon\Automation\Changelog\Source\HTMLSource;
use Aeon\Automation\Changelog\Source\MarkdownSource;
use Aeon\Automation\GitHub\File;

final class SourceFactory
{
    public function create(string $format, File $file) : Source
    {
        switch (\strtolower($format)) {
            case 'markdown':
                return new MarkdownSource($file->content());

                break;
            case 'html':
                return new HTMLSource($file->content());

                break;

            default:
                throw new \RuntimeException('Unknown format ' . $format);
        }
    }
}

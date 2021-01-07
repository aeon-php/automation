<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

final class FormatterFactory
{
    private string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function create(string $format, string $theme) : Formatter
    {
        switch (\trim(\strtolower($format))) {
            case 'markdown' :
            case 'html' :
                return  new TwigFormatter(
                    $this->rootDir,
                    \trim(\strtolower($format)),
                    \trim(\strtolower($theme))
                );

            default:
                throw new \RuntimeException('Invalid format: ' . $format);
        }
    }
}

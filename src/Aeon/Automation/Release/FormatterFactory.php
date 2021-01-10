<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Configuration;
use Aeon\Automation\Twig\Release\TwigFormatter;

final class FormatterFactory
{
    private Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function create(string $format, string $theme) : Formatter
    {
        switch (\trim(\strtolower($format))) {
            case 'markdown' :
            case 'html' :
                return  new TwigFormatter(
                    $this->configuration->rootDir(),
                    \trim(\strtolower($format)),
                    \trim(\strtolower($theme))
                );

            default:
                throw new \RuntimeException('Invalid format: ' . $format);
        }
    }
}

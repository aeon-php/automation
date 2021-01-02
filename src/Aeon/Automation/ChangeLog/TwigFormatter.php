<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\Release;
use Aeon\Automation\Twig\ChangeLogExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigFormatter implements Formatter
{
    private Environment $twig;

    private string $format;

    public function __construct(string $rootDir, string $format)
    {
        $format = \strtolower($format);

        if (!\file_exists($rootDir . '/resources/templates/' . $format)) {
            throw new \InvalidArgumentException('Templates directory does not exists: ' . $rootDir . '/resources/templates/' . $format);
        }

        if (!\in_array($format, ['markdown', 'html'], true)) {
            throw new \InvalidArgumentException('Invalid form: ' . $format);
        }

        $this->twig = new Environment(new FilesystemLoader($rootDir . '/resources/templates/' . $format), []);
        $this->twig->addExtension(new ChangeLogExtension());
        $this->format = $format;
    }

    public function format(Release $release) : string
    {
        switch ($this->format) {
            case 'markdown':
                return $this->twig->render('changelog.md.twig', ['release' => $release]);
            case 'html':
                return $this->twig->render('changelog.html.twig', ['release' => $release]);

            default:

                throw new \RuntimeException('Unknown format ' . $this->format);
        }
    }
}

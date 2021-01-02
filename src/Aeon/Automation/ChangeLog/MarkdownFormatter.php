<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\Release;
use Aeon\Automation\Twig\ChangeLogExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class MarkdownFormatter implements Formatter
{
    private Environment $twig;

    public function __construct(string $rootDir)
    {
        if (!\file_exists($rootDir . '/resources/templates/markdown')) {
            throw new \InvalidArgumentException('Templates directory does not exists: ' . $rootDir . '/resources/templates/markdown');
        }

        $this->twig = new Environment(new FilesystemLoader($rootDir . '/resources/templates/markdown'), []);
        $this->twig->addExtension(new ChangeLogExtension());
    }

    public function format(Release $release) : string
    {
        return $this->twig->render('changelog.md.twig', ['release' => $release]);
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Twig\Release;

use Aeon\Automation\Release;
use Aeon\Automation\Release\Formatter;
use Aeon\Automation\Twig\ChangeLogExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigFormatter implements Formatter
{
    private Environment $twig;

    private string $format;

    private string $theme;

    private bool $footer;

    public function __construct(string $rootDir, string $format, string $theme)
    {
        $format = \strtolower($format);
        $theme = \strtolower($theme);

        if (!\file_exists($rootDir . '/resources/templates/' . $format)) {
            throw new \InvalidArgumentException('Templates directory does not exists: ' . $rootDir . '/resources/templates/' . $format);
        }

        if (!\in_array($format, ['markdown', 'html'], true)) {
            throw new \InvalidArgumentException('Invalid format: ' . $format);
        }

        if (!\in_array($theme, ['keepachangelog', 'classic'], true)) {
            throw new \InvalidArgumentException('Invalid theme: ' . $theme);
        }

        $this->twig = new Environment(new FilesystemLoader($rootDir . '/resources/templates/' . $format . '/' . $theme), []);
        $this->twig->addExtension(new ChangeLogExtension());
        $this->format = $format;
        $this->theme = $theme;
        $this->footer = true;
    }

    public function disableFooter() : self
    {
        $this->footer = false;

        return $this;
    }

    public function formatRelease(Release $release) : string
    {
        switch ($this->format) {
            case 'markdown':
                return $this->twig->render('changelog.md.twig', ['release' => $release, 'footer' => $this->footer]);
            case 'html':
                return $this->twig->render('changelog.html.twig', ['release' => $release, 'footer' => $this->footer]);

            default:

                throw new \RuntimeException('Unknown format ' . $this->format);
        }
    }

    public function formatFooter() : string
    {
        switch ($this->format) {
            case 'markdown':
                return $this->twig->render('footer.md.twig');
            case 'html':
                return $this->twig->render('footer.html.twig');

            default:

                throw new \RuntimeException('Unknown format ' . $this->format);
        }
    }
}

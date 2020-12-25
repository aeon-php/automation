<?php

declare(strict_types=1);

namespace Aeon\Automation;

final class Project
{
    private string $path;

    public function __construct(string $path)
    {
        if (!\file_exists($path)) {
            throw new \InvalidArgumentException("Invalid project path: {$path}");
        }

        $this->path = \rtrim($path, '/');
    }

    public function organization() : string
    {
        $composerJson = $this->composerJson();

        if (!isset($composerJson['name'])) {
            throw new \RuntimeException('Missing name in composer.json: ' . $this->composerJsonPath());
        }

        return \explode('/', $composerJson['name'])[0];
    }

    public function name() : string
    {
        $composerJson = $this->composerJson();

        if (!isset($composerJson['name'])) {
            throw new \RuntimeException('Missing name in composer.json: ' . $this->composerJsonPath());
        }

        return \explode('/', $composerJson['name'])[1];
    }

    private function composerJson() : array
    {
        if (!\file_exists($this->composerJsonPath())) {
            throw new \RuntimeException('Missing composer.json: ' . $this->composerJsonPath());
        }

        $composerJson = \json_decode(\file_get_contents($this->composerJsonPath()), true);

        if (!isset($composerJson['name'])) {
            throw new \RuntimeException('Missing name in composer.json: ' . $this->composerJsonPath());
        }

        return $composerJson;
    }

    /**
     * @return string
     */
    private function composerJsonPath() : string
    {
        return $this->path . '/composer.json';
    }
}

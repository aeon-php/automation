<?php

declare(strict_types=1);

namespace Aeon\Automation;

final class Configuration
{
    private array $defaultPaths;

    private ?string $path;

    private ?\DOMDocument $config;

    public function __construct(array $defaultPaths, ?string $path = null)
    {
        $this->defaultPaths = $defaultPaths;
        $this->path = $path;
        $this->config = null;
    }

    public function githubAccessToken() : ?string
    {
        return \getenv('AEON_AUTOMATION_GH_TOKEN');
    }

    /**
     * @var Project[]
     */
    public function projects() : array
    {
        $projects = [];

        foreach ($this->config()->getElementsByTagName('project') as $project) {
            $projects[] = new Project($project->attributes->getNamedItem('name')->nodeValue);
        }

        return $projects;
    }

    public function project(string $name) : Project
    {
        foreach ($this->projects() as $project) {
            if ($project->is($name)) {
                return $project;
            }
        }

        return new Project($name);
    }

    private function config() : \DOMDocument
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $configFilePath = $this->path;

        if ($configFilePath === null) {
            foreach ($this->defaultPaths as $defaultPath) {
                $automationConfiguration = \realpath($defaultPath . '/automation.xml');

                if ($automationConfiguration !== false && \file_exists($automationConfiguration)) {
                    $configFilePath = $automationConfiguration;

                    break;
                }
            }
        }

        if ($configFilePath === null || !\file_exists($configFilePath)) {
            $this->config = new \DOMDocument();

            return $this->config;
        }

        $this->config = new \DOMDocument();
        $this->config->loadXML(\file_get_contents($configFilePath));

        return $this->config;
    }
}

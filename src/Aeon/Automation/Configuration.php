<?php

declare(strict_types=1);

namespace Aeon\Automation;

final class Configuration
{
    private array $defaultPaths;

    private ?string $path;

    /**
     * @var Project[]
     */
    private array $projects;

    public function __construct(array $defaultPaths, ?string $path = null)
    {
        $this->defaultPaths = $defaultPaths;
        $this->path = $path;
        $this->projects = [];
    }

    /**
     * @var Project[]
     */
    public function projects() : array
    {
        if (\count($this->projects)) {
            return $this->projects;
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

        $config = new \DOMDocument();
        $config->loadXML(\file_get_contents($configFilePath));

        $projects = [];
        /** @var \DOMNode $project */
        foreach ($config->getElementsByTagName('project') as $project) {
            $projects[] = new Project($project->attributes->getNamedItem('path')->nodeValue);
        }

        $this->projects = $projects;

        return $this->projects;
    }

    public function project(string $name) : Project
    {
        foreach ($this->projects() as $project) {
            if (\strtolower($project->name()) === \strtolower($name)) {
                return $project;
            }
        }

        throw new \RuntimeException("Project with name \"{$name}\" does not exists in the automation.xml configuration");
    }
}

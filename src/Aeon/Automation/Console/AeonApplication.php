<?php

declare(strict_types=1);

namespace Aeon\Automation\Console;

use Aeon\Automation\Console\Command\Help;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputOption;

final class AeonApplication extends Application
{
    protected function getDefaultCommands() : array
    {
        return [new Help(), new ListCommand()];
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.'));
        $definition->addOption(new InputOption('github-token', 'gt', InputOption::VALUE_REQUIRED, 'Github personal access token, generated here: https://github.com/settings/tokens By default taken from AEON_AUTOMATION_GH_TOKEN env variable'));

        return $definition;
    }
}

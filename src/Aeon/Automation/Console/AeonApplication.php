<?php

declare(strict_types=1);

namespace Aeon\Automation\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

final class AeonApplication extends Application
{
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Custom path to the automation.xml configuration file.'));
        $definition->addOption(new InputOption('github-token', 'gt', InputOption::VALUE_REQUIRED, 'Github personal access token, generated here: https://github.com/settings/tokens By default taken from <fg=yellow>AEON_AUTOMATION_GH_TOKEN</> env variable'));

        return $definition;
    }
}

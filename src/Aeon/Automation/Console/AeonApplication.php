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

        $definition->addOption(
            new InputOption(
                'configuration',
                'c',
                InputOption::VALUE_REQUIRED,
                'Custom path to the automation.xml configuration file.'
            )
        );
        $definition->addOption(new InputOption(
            'github-token',
            'gt',
            InputOption::VALUE_REQUIRED,
            'Github personal access token, generated here: https://github.com/settings/tokens By default taken from <fg=yellow>AEON_AUTOMATION_GH_TOKEN</> env variable'
        ));
        $definition->addOption(new InputOption(
            'github-enterprise-url',
            'geu',
            InputOption::VALUE_REQUIRED,
            'Github enterprise URL, by default taken from <fg=yellow>AEON_AUTOMATION_GH_ENTERPRISE_URL</> env variable',
            \getenv('AEON_AUTOMATION_GH_ENTERPRISE_URL') ? \getenv('AEON_AUTOMATION_GH_ENTERPRISE_URL') : null
        ));

        return $definition;
    }
}

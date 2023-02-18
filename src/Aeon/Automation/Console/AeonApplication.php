<?php

declare(strict_types=1);

namespace Aeon\Automation\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

final class AeonApplication extends Application
{
    protected function getDefaultInputDefinition() : InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(
            new InputOption(
                'configuration',
                null,
                InputOption::VALUE_REQUIRED,
                'Custom path to the automation.xml configuration file.'
            )
        );
        $definition->addOption(new InputOption(
            'cache-path',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to root cache directory, taken from sys_get_tmp_dir() function or <fg=yellow>AEON_AUTOMATION_CACHE_DIR</> env variable',
            \getenv('AEON_AUTOMATION_CACHE_DIR')
                ? \getenv('AEON_AUTOMATION_CACHE_DIR')
                : (
                    \getenv('HOME') !== false
                    ? (\getenv('HOME') . DIRECTORY_SEPARATOR . '.automation')
                    : (
                        (\getenv('USERPROFILE') !== false)
                        ? \getenv('USERPROFILE') . DIRECTORY_SEPARATOR . '.automation'
                        : \sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.automation'
                    )
                )
        ));
        $definition->addOption(new InputOption(
            'github-token',
            null,
            InputOption::VALUE_REQUIRED,
            'Github personal access token, generated here: https://github.com/settings/tokens By default taken from <fg=yellow>AEON_AUTOMATION_GH_TOKEN</> env variable'
        ));
        $definition->addOption(new InputOption(
            'github-enterprise-url',
            null,
            InputOption::VALUE_REQUIRED,
            'Github enterprise URL, by default taken from <fg=yellow>AEON_AUTOMATION_GH_ENTERPRISE_URL</> env variable',
            \getenv('AEON_AUTOMATION_GH_ENTERPRISE_URL') ? \getenv('AEON_AUTOMATION_GH_ENTERPRISE_URL') : null
        ));

        return $definition;
    }
}

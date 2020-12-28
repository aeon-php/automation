<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Symfony\Component\Console\Command\HelpCommand;

final class Help extends HelpCommand
{
    protected function configure() : void
    {
        parent::configure();
    }
}

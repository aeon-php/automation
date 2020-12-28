<?php

declare(strict_types=1);

namespace Aeon\Automation\Console;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class AeonStyle extends SymfonyStyle
{
    public function note($message) : void
    {
        if ($this->getVerbosity() > ConsoleOutput::VERBOSITY_NORMAL) {
            parent::note($message);
        }
    }

    public function title($message) : void
    {
        if ($this->getVerbosity() > ConsoleOutput::VERBOSITY_NORMAL) {
            parent::title($message);
        }
    }

    public function warning($message) : void
    {
        if ($this->getVerbosity() > ConsoleOutput::VERBOSITY_NORMAL) {
            parent::warning($message);
        }
    }

    public function progressStart(int $max = 0) : void
    {
        if ($this->getVerbosity() > ConsoleOutput::VERBOSITY_NORMAL) {
            parent::progressStart($max);
        }
    }

    public function progressAdvance(int $step = 1) : void
    {
        if ($this->getVerbosity() > ConsoleOutput::VERBOSITY_NORMAL) {
            parent::progressAdvance($step);
        }
    }

    public function progressFinish() : void
    {
        if ($this->getVerbosity() > ConsoleOutput::VERBOSITY_NORMAL) {
            parent::progressFinish();
            $this->newLine(2);
        }
    }
}

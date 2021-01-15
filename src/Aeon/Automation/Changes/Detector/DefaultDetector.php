<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\Detector;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesDetector;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Type;

final class DefaultDetector implements ChangesDetector
{
    public function support(ChangesSource $changesSource) : bool
    {
        return true;
    }

    public function detect(ChangesSource $changesSource) : Changes
    {
        return new Changes(
            new Change($changesSource, Type::changed(), $changesSource->title())
        );
    }
}

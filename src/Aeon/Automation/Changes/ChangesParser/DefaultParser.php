<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\ChangesParser;

use Aeon\Automation\Changes;
use Aeon\Automation\Changes\ChangesParser;
use Aeon\Automation\ChangesSource;

final class DefaultParser implements ChangesParser
{
    public function support(ChangesSource $changesSource) : bool
    {
        return true;
    }

    public function parse(ChangesSource $changesSource) : Changes
    {
        return new Changes(
            $changesSource,
            new Changes\Change($changesSource, Changes\Type::changed(), $changesSource->title())
        );
    }
}

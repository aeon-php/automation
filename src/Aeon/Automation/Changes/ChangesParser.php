<?php declare(strict_types=1);

namespace Aeon\Automation\Changes;

use Aeon\Automation\Changes;
use Aeon\Automation\ChangesSource;

interface ChangesParser
{
    public function support(ChangesSource $changesSource) : bool;

    public function parse(ChangesSource $changesSource) : Changes;
}

<?php declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\ChangeLog;

interface Formatter
{
    public function format(ChangeLog $changeLog) : string;
}

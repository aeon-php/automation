<?php declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\Release;

interface Formatter
{
    public function format(Release $release) : string;
}

<?php declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Release;

interface Formatter
{
    public function format() : string;

    public function theme() : string;

    public function formatRelease(Release $release) : string;
}

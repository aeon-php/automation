<?php declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Release;
use Aeon\Automation\Releases;

interface Formatter
{
    public function formatRelease(Release $release) : string;

    public function formatReleases(Releases $releases) : string;
}

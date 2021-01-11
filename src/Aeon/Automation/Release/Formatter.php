<?php declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Release;

interface Formatter
{
    public function disableFooter() : self;

    public function formatRelease(Release $release) : string;

    public function formatFooter() : string;
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Twig;

use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\GitHub\PullRequest;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ChangeLogExtension extends AbstractExtension
{
    public function getFilters() : array
    {
        return [
            new TwigFilter('is_commit', [$this, 'isCommit']),
            new TwigFilter('is_pr', [$this, 'isPullRequest']),
        ];
    }

    public function isCommit(ChangesSource $source) : bool
    {
        return $source instanceof Commit;
    }

    public function isPullRequest(ChangesSource $source) : bool
    {
        return $source instanceof PullRequest;
    }
}

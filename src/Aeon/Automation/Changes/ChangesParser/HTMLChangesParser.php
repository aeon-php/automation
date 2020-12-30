<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\ChangesParser;

use Aeon\Automation\Changes;
use Aeon\Automation\Changes\ChangesParser;
use Aeon\Automation\ChangesSource;
use Symfony\Component\DomCrawler\Crawler;

final class HTMLChangesParser implements ChangesParser
{
    public function support(ChangesSource $changesSource) : bool
    {
        if (\strip_tags($changesSource->description()) === $changesSource->description()) {
            return false;
        }

        $crawler = new Crawler('<html><body><div id="changes">' . $changesSource->description() . '</div></body></html>');

        if (!$crawler->filter('#change-log')->count()) {
            return false;
        }

        return true;
    }

    public function parse(ChangesSource $changesSource) : Changes
    {
        $crawler = new Crawler('<html><body><div id="changes">' . $changesSource->description() . '</div></body></html>');

        if (!$crawler->filter('#change-log')->count()) {
            throw new \RuntimeException("Invalid html format, can't extract changes");
        }

        $changes = [];

        foreach (Changes\Type::all() as $type) {
            if ($crawler->filter('ul#' . $type->name())->count()) {
                foreach ($crawler->filter('ul#' . $type->name())->children('li') as $node) {
                    $changes[] = new Changes\Change($type, $node->textContent);
                }
            }
        }

        return new Changes($changesSource, ...$changes);
    }
}

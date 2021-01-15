<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\Detector;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesDetector;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Type;
use Symfony\Component\DomCrawler\Crawler;

final class HTMLChangesDetector implements ChangesDetector
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

    public function detect(ChangesSource $changesSource) : Changes
    {
        $crawler = new Crawler('<html><body><div id="changes">' . $changesSource->description() . '</div></body></html>');

        if (!$crawler->filter('#change-log')->count()) {
            throw new \RuntimeException("Invalid html format, can't extract changes");
        }

        $changes = [];

        foreach (Type::all() as $type) {
            if ($crawler->filter('ul#' . $type->name())->count()) {
                foreach ($crawler->filter('ul#' . $type->name())->children('li') as $node) {
                    $changes[] = new Change($changesSource, $type, $node->textContent);
                }
            }
        }

        return new Changes(...$changes);
    }
}

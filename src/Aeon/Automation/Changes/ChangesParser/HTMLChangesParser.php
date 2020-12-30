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
            return new Changes($changesSource, [], [$changesSource->title()], [], [], [], []);
        }

        $added = [];

        if ($crawler->filter('ul#added')->count()) {
            foreach ($crawler->filter('ul#added')->children('li') as $node) {
                $added[] = $node->textContent;
            }
        }

        $changed = [];

        if ($crawler->filter('ul#changed')->count()) {
            foreach ($crawler->filter('ul#changed')->children('li') as $node) {
                $changed[] = $node->textContent;
            }
        }

        $fixed = [];

        if ($crawler->filter('ul#fixed')->count()) {
            foreach ($crawler->filter('ul#fixed')->children('li') as $node) {
                $fixed[] = $node->textContent;
            }
        }

        $removed = [];

        if ($crawler->filter('ul#removed')->count()) {
            foreach ($crawler->filter('ul#removed')->children('li') as $node) {
                $removed[] = $node->textContent;
            }
        }

        $deprecated = [];

        if ($crawler->filter('ul#deprecated')->count()) {
            foreach ($crawler->filter('ul#deprecated')->children('li') as $node) {
                $deprecated[] = $node->textContent;
            }
        }

        $security = [];

        if ($crawler->filter('ul#security')->count()) {
            foreach ($crawler->filter('ul#security')->children('li') as $node) {
                $security[] = $node->textContent;
            }
        }

        return new Changes($changesSource, $added, $changed, $fixed, $removed, $deprecated, $security);
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Changes;
use Aeon\Automation\ChangesSource;
use Symfony\Component\DomCrawler\Crawler;

final class PullRequest implements ChangesSource
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function id() : string
    {
        return (string) $this->data['number'];
    }

    public function url() : string
    {
        return $this->data['html_url'];
    }

    public function title() : string
    {
        return $this->data['title'];
    }

    public function user() : string
    {
        return $this->data['user']['login'];
    }

    public function userUrl() : string
    {
        return $this->data['user']['html_url'];
    }

    public function haveHTML() : bool
    {
        if (!isset($this->data['body'])) {
            return false;
        }

        return \strip_tags($this->data['body']) !== $this->data['body'];
    }

    public function haveChangesDescription() : bool
    {
        $crawler = $this->crawler();

        return (bool) $crawler->filter('#change-log')->count();
    }

    public function changes() : Changes
    {
        if (!$this->haveHTML()) {
            return new Changes($this, [], [$this->title()], [], [], [], []);
        }

        $crawler = $this->crawler();

        if (!$crawler->filter('#change-log')->count()) {
            return new Changes($this, [], [$this->title()], [], [], [], []);
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

        return new Changes($this, $added, $changed, $fixed, $removed, $deprecated, $security);
    }

    public function isMerged() : bool
    {
        return $this->data['merged_at'] !== null;
    }

    public function hasMilestone() : bool
    {
        return isset($this->data['milestone']);
    }

    public function equals(ChangesSource $source) : bool
    {
        return $source->id() === $this->id();
    }

    /**
     * @return Crawler
     */
    private function crawler() : Crawler
    {
        if (!isset($this->data['body'])) {
            return new Crawler('<html><body></body></html>');
        }

        return new Crawler('<html><body><div id="pull-request-body">' . $this->data['body'] . '</div></body></html>');
    }
}

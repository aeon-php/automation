<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Symfony\Component\DomCrawler\Crawler;

final class PullRequest
{
    private string $number;

    private string $url;

    private string $title;

    private string $user;

    private string $userUrl;

    private string $body;

    public function __construct(string $number, string $url, string $title, string $user, string $userUrl, string $body)
    {
        $this->number = $number;
        $this->url = $url;
        $this->body = $body;
        $this->title = $title;
        $this->user = $user;
        $this->userUrl = $userUrl;
    }

    public function number() : string
    {
        return $this->number;
    }

    public function url() : string
    {
        return $this->url;
    }

    public function title() : string
    {
        return $this->title;
    }

    public function user() : string
    {
        return $this->user;
    }

    public function userUrl() : string
    {
        return $this->userUrl;
    }

    public function haveHTML() : bool
    {
        return \strip_tags($this->body) !== $this->body;
    }

    public function haveChangesDescription() : bool
    {
        $crawler = $this->crawler();

        return (bool) $crawler->filter('#change-log')->count();
    }

    public function changes() : Changes
    {
        if (!$this->haveHTML()) {
            return new Changes($this, [], [$this->title], [], [], [], []);
        }

        $crawler = $this->crawler();

        if (!$crawler->filter('#change-log')->count()) {
            return new Changes($this, [], [$this->title], [], [], [], []);
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

    /**
     * @return Crawler
     */
    private function crawler() : Crawler
    {
        return new Crawler('<html><body><div id="pull-request-body">' . $this->body . '</div></body></html>');
    }
}

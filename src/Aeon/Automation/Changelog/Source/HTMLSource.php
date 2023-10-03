<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog\Source;

use Aeon\Automation\Changelog\Source;
use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Type;
use Aeon\Automation\Release;
use Aeon\Automation\Releases;
use Aeon\Calendar\Gregorian\DateTime;
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\TimeUnit;
use Symfony\Component\DomCrawler\Crawler;

final class HTMLSource implements Source
{
    private const PATTERN_RELEASE = '/^\[(.*)\] \- (.*)$/';

    private string $content;

    public function __construct(string $content)
    {
        if (\strlen($content) === \strlen(\strip_tags($content)) && !empty($this->content)) {
            throw new \InvalidArgumentException('HTML Changelog source requires valid html content');
        }

        $this->content = \htmlspecialchars_decode($content);
    }

    public function releases() : Releases
    {
        $changelogContent = new Crawler($this->content);

        $currentDate = DateTime::fromString('2005-04-01 00:00:00 UTC'); // Git release date: 7 April 2005
        $releases = new Releases();

        $change = 0;

        foreach ($changelogContent->filter('h2 ~ h3') as $changeTypeNode) {
            $releaseNode = $this->skipToPreviousSibling($changeTypeNode, 'h2');

            if (!\preg_match(self::PATTERN_RELEASE, $releaseNode->nodeValue, $releaseParts)) {
                continue;
            }

            $releaseName = $releaseParts[1];
            $releaseDate = Day::fromString($releaseParts[2]);

            $changesNode = new Crawler($this->skipToNextSibling($changeTypeNode, 'ul'));
            $changeNodes = $changesNode->filter('li');

            if (!$changesNode->count()) {
                continue;
            }

            foreach ($changeNodes as $changeNode) {
                $change += 1;

                $changeNodeContent = new Crawler($changeNode);

                $sourceNode = $changeNodeContent->filterXPath('.//a[1]');
                $descriptionNode = $changeNodeContent->filterXPath('.//strong[1]');
                $userNode = $changeNodeContent->filterXPath('.//a[last()]');

                $changes = new Changes(
                    new Change(
                        new ChangesSource(
                            \strpos($sourceNode->text(), '#') === 0 ? ChangesSource::TYPE_PULL_REQUEST : ChangesSource::TYPE_COMMIT,
                            \strpos($sourceNode->text(), '#') === 0 ? \substr($sourceNode->text(), 1) : $sourceNode->text(),
                            $sourceNode->attr('href'),
                            $descriptionNode->text(),
                            $descriptionNode->text(),
                            $currentDate->sub(TimeUnit::seconds($change)),
                            \substr($userNode->text(), 1),
                            $userNode->attr('href')
                        ),
                        Type::fromString($changeTypeNode->nodeValue),
                        $descriptionNode->text(),
                    )
                );

                if ($releases->has($releaseName)) {
                    if ($releases->get($releaseName)->hasFrom($changes->source())) {
                        $releases->get($releaseName)->replace($releases->get($releaseName)->getFrom($changes->source())->merge($changes));
                    } else {
                        $releases->get($releaseName)->add($changes);
                    }
                } else {
                    $release = new Release($releaseName, $releaseDate);
                    $release->add($changes);

                    $releases = $releases->add($release);
                }
            }
        }

        return $releases;
    }

    private function skipToNextSibling(\DOMNode $node, string $nodeName) : ?\DOMNode
    {
        $sibling = $node->nextSibling;

        while ($sibling->nextSibling !== null && $sibling->nodeName !== $nodeName) {
            $sibling = $sibling->nextSibling;
        }

        if ($sibling->nodeName === '#text') {
            return null;
        }

        return $sibling;
    }

    private function skipToPreviousSibling(\DOMNode $node, string $nodeName) : ?\DOMNode
    {
        $sibling = $node->previousSibling;

        while ($sibling->nextSibling !== null && $sibling->nodeName !== $nodeName) {
            $sibling = $sibling->previousSibling;
        }

        if ($sibling->nodeName === '#text') {
            return null;
        }

        return $sibling;
    }
}

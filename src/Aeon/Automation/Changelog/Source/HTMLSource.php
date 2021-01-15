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
use Symfony\Component\DomCrawler\Crawler;

final class HTMLSource implements Source
{
    private const PATTERN_RELEASE = '/^\[(.*)\] \- (.*)$/';

    private string $content;

    public function __construct(string $content)
    {
        if (\strlen($content) === \strlen(\strip_tags($content))) {
            throw new \InvalidArgumentException('HTML Changelog source requires valid html content');
        }

        $this->content = $content;
    }

    public function releases() : Releases
    {
        $changelogContent = new Crawler($this->content);

        $currentDate = DateTime::fromString('now');
        $releases = new Releases();

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
                $changeNodeContent = (new Crawler($changeNode));

                $sourceNode = $changeNodeContent->filter('a:first-child');
                $descriptionNode = $changeNodeContent->filter('strong');
                $userNode = $changeNodeContent->filter('a:last-of-type');

                $changes = new Changes(
                    new Change(
                        new ChangesSource(
                            \strpos($sourceNode->text(), '#') === 0 ? ChangesSource::TYPE_PULL_REQUEST : ChangesSource::TYPE_COMMIT,
                            \strpos($sourceNode->text(), '#') === 0 ? \substr($sourceNode->text(), 1, \strlen($sourceNode->text()) - 1) : $sourceNode->text(),
                            $sourceNode->attr('href'),
                            $descriptionNode->text(),
                            $descriptionNode->text(),
                            $currentDate,
                            \substr($userNode->text(), 1, \strlen($userNode->text()) - 1),
                            $userNode->attr('href')
                        ),
                        Type::fromString($changeTypeNode->nodeValue),
                        $descriptionNode->text(),
                    )
                );

                if ($releases->has($releaseName)) {
                    if ($releases->get($releaseName)->hasFrom($changes->source())) {
                        $releases->get($releaseName)->getFrom($changes->source())->merge($changes);
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

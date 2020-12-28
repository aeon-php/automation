<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Project;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Github\Client;

final class Tags
{
    /**
     * @var Tag[]
     */
    private array $tags;

    public function __construct(Tag ...$tags)
    {
        $this->tags = $tags;
    }

    public static function getAll(Client $client, Project $project) : self
    {
        return new self(...\array_map(
            fn (array $tagData) : Tag => new Tag($tagData),
            $client->repository()->tags($project->organization(), $project->name())
        ));
    }

    /**
     * @return Tag[]
     */
    public function all()
    {
        return $this->tags;
    }

    public function onlyValidSemVer() : self
    {
        $parser = new VersionParser();

        $tags = [];

        foreach ($this->tags as $tag) {
            try {
                $parser->normalize($tag->name());
                $tags[] = $tag;
            } catch (\UnexpectedValueException $e) {
            }
        }

        return new self(...$tags);
    }

    public function semVerRsort() : self
    {
        $sortedNames = Semver::rsort(\array_map(fn (Tag $releaseData) : string => $releaseData->name(), $this->onlyValidSemVer()->all()));
        $tags = [];

        foreach ($sortedNames as $sortedName) {
            foreach ($this->tags as $tag) {
                if ($tag->name() === $sortedName) {
                    $tags[] = $tag;
                }
            }
        }

        return new self(...$tags);
    }

    public function semVerSort() : self
    {
        $sortedNames = Semver::sort(\array_map(fn (Tag $releaseData) : string => $releaseData->name(), $this->onlyValidSemVer()->all()));
        $tags = [];

        foreach ($sortedNames as $sortedName) {
            foreach ($this->tags as $tag) {
                if ($tag->name() === $sortedName) {
                    $tags[] = $tag;
                }
            }
        }

        return new self(...$tags);
    }

    public function first() : ?Tag
    {
        if (!$this->count()) {
            return null;
        }

        return \current($this->tags);
    }

    public function count() : int
    {
        return \count($this->tags);
    }
}

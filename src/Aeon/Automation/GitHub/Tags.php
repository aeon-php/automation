<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

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

    /**
     * @return Tag[]
     */
    public function all() : array
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

    public function last() : ?Tag
    {
        if (!$this->count()) {
            return null;
        }

        return \end($this->tags);
    }

    public function next(string $tag) : ?Tag
    {
        $found = false;

        foreach ($this->tags as $nextTag) {
            if ($found) {
                return $nextTag;
            }

            if ($nextTag->name() === $tag) {
                $found = true;
            }
        }

        return null;
    }

    public function count() : int
    {
        return \count($this->tags);
    }

    public function limit(int $limit) : self
    {
        return new self(...\array_slice($this->tags, 0, $limit));
    }

    public function exists(string $name) : bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->name() === $name) {
                return true;
            }
        }

        return false;
    }

    public function since(string $name) : self
    {
        $tags = [];

        $before = true;

        foreach ($this->tags as $tag) {
            if ($tag->name() === $name) {
                $before = false;
            }

            if (!$before) {
                $tags[] = $tag;
            }
        }

        return new self(...$tags);
    }

    public function until(string $name) : self
    {
        $tags = [];

        foreach ($this->tags as $tag) {
            if ($tag->name() === $name) {
                $tags[] = $tag;

                break;
            }

            $tags[] = $tag;
        }

        return new self(...$tags);
    }

    public function without(array $tagsSkip) : self
    {
        $tags = [];

        foreach ($this->tags as $tag) {
            if (\in_array($tag->name(), $tagsSkip, true)) {
                continue;
            }

            $tags[] = $tag;
        }

        return new self(...$tags);
    }
}

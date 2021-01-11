<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

final class Releases
{
    /**
     * @var Release[]
     */
    private array $releases;

    public function __construct(Release ...$releases)
    {
        $this->releases = $releases;
    }

    /**
     * @return Release[]
     */
    public function all() : array
    {
        return $this->releases;
    }

    public function onlyValidSemVer() : self
    {
        $parser = new VersionParser();

        $releases = [];

        foreach ($this->releases as $release) {
            try {
                $parser->normalize($release->name());
                $releases[] = $release;
            } catch (\UnexpectedValueException $e) {
            }
        }

        return new self(...$releases);
    }

    public function semVerRsort() : self
    {
        $sortedNames = Semver::rsort(\array_map(fn (Release $release) : string => $release->name(), $this->onlyValidSemVer()->all()));
        $releases = [];

        foreach ($sortedNames as $sortedName) {
            foreach ($this->releases as $release) {
                if ($release->name() === $sortedName) {
                    $releases[] = $release;
                }
            }
        }

        return new self(...$releases);
    }

    public function semVerSort() : self
    {
        $sortedNames = Semver::sort(\array_map(fn (Release $release) : string => $release->name(), $this->onlyValidSemVer()->all()));
        $releases = [];

        foreach ($sortedNames as $sortedName) {
            foreach ($this->releases as $release) {
                if ($release->name() === $sortedName) {
                    $releases[] = $release;
                }
            }
        }

        return new self(...$releases);
    }

    public function first() : ?Release
    {
        if (!$this->count()) {
            return null;
        }

        return \current($this->releases);
    }

    public function last() : ?Release
    {
        if (!$this->count()) {
            return null;
        }

        return \end($this->releases);
    }

    public function next(string $release) : ?Release
    {
        $found = false;

        foreach ($this->releases as $nextRelease) {
            if ($found) {
                return $nextRelease;
            }

            if ($nextRelease->name() === $release) {
                $found = true;
            }
        }

        return null;
    }

    public function count() : int
    {
        return \count($this->releases);
    }

    public function limit(int $limit) : self
    {
        return new self(...\array_slice($this->releases, 0, $limit));
    }

    public function exists(string $title) : bool
    {
        foreach ($this->releases as $release) {
            if ($release->name() === $title) {
                return true;
            }
        }

        return false;
    }

    public function get(string $name) : Release
    {
        foreach ($this->releases as $release) {
            if ($release->name() === $name) {
                return $release;
            }
        }

        throw new \RuntimeException('Release ' . $name . ' not found');
    }
}

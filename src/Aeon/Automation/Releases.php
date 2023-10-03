<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Aeon\Automation\Changes\Contributor;
use Composer\Semver\Comparator;

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

    public function count() : int
    {
        return \count($this->releases);
    }

    public function has(string $name) : bool
    {
        foreach ($this->releases as $release) {
            if (\strtolower($release->name()) === \strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    public function add(Release $release) : self
    {
        if ($this->has($release->name())) {
            throw new \InvalidArgumentException("Release \"{$release->name()}\" already exists");
        }

        return new self(...\array_merge($this->releases, [$release]));
    }

    public function get(string $name) : Release
    {
        foreach ($this->releases as $release) {
            if (\strtolower($release->name()) === \strtolower($name)) {
                return $release;
            }
        }

        throw new \InvalidArgumentException("Release \"{$name}\" already exists");
    }

    /**
     * @return Release[]
     */
    public function all() : array
    {
        return $this->releases;
    }

    /**
     * @return array<Contributor>
     */
    public function contributors() : array
    {
        /** @var array<string, Contributor> $contributors */
        $contributors = [];

        foreach ($this->releases as $release) {
            foreach ($release->contributors() as $contributor) {
                if (!\array_key_exists($contributor->name(), $contributors)) {
                    $contributors[$contributor->name()] = $contributor;
                }
            }
        }

        \uasort($contributors, static function (Contributor $authorA, Contributor $authorB) : int {
            return \strtolower($authorA->name()) <=> \strtolower($authorB->name());
        });

        return \array_values($contributors);
    }

    public function remove(string $name) : self
    {
        $releases = [];

        foreach ($this->releases as $existingRelease) {
            if (\strtolower($existingRelease->name()) === \strtolower($name)) {
                continue;
            }

            $releases[] = $existingRelease;
        }

        return new self(...$releases);
    }

    public function replace(string $name, Release $release) : self
    {
        $releases = [];

        foreach ($this->releases as $existingRelease) {
            $releases[] = (\strtolower($existingRelease->name()) === \strtolower($name)) ? $release : $existingRelease;
        }

        return new self(...$releases);
    }

    public function update(Release $release) : self
    {
        $releases = [];

        foreach ($this->releases as $existingRelease) {
            $releases[] = (\strtolower($existingRelease->name()) === \strtolower($release->name()))
                ? $release->isEqual($existingRelease) ? $existingRelease : $release
                : $existingRelease;
        }

        return new self(...$releases);
    }

    public function sort() : self
    {
        $releases = $this->releases;

        \uasort($releases, function (Release $releaseA, Release $releaseB) : int {
            if ($releaseB->isUnreleased()) {
                return 1;
            }

            if ($releaseA->isUnreleased()) {
                return -1;
            }

            if (Comparator::greaterThan($releaseB->name(), $releaseA->name())) {
                return 1;
            }

            if (Comparator::lessThan($releaseB->name(), $releaseA->name())) {
                return -1;
            }

            return $releaseB->day()->toDateTimeImmutable() <=> $releaseA->day()->toDateTimeImmutable();
        });

        return new self(...$releases);
    }
}

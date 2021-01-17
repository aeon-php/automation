<?php

declare(strict_types=1);

namespace Aeon\Automation\Changelog;

use Aeon\Automation\Release;
use Aeon\Automation\Releases;
use Aeon\Calendar\Gregorian\Day;

final class Manipulator
{
    public function update(Source $source, Release $release) : Releases
    {
        $releases = $source->releases();

        if (!$releases->has($release->name())) {
            return $releases->add($release);
        }

        return $releases->update($release);
    }

    public function release(Source $source, string $newRelease, Day $day) : Releases
    {
        $releases = $source->releases();

        if (!$releases->has('Unreleased')) {
            throw new \InvalidArgumentException('There is nothing to release');
        }

        return $releases->replace('Unreleased', $releases->get('Unreleased')->update($newRelease, $day));
    }

    public function updateAll(Source $source, Releases $releases)
    {
        $sourceReleases = $source->releases();

        foreach ($releases->all() as $release) {
            if (!$sourceReleases->has($release->name())) {
                $sourceReleases = $sourceReleases->add($release);
            } else {
                $sourceReleases = $sourceReleases->update($release);
            }
        }

        return $sourceReleases;
    }
}

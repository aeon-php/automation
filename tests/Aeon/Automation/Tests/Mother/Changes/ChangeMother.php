<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother\Changes;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Type;

final class ChangeMother
{
    public static function pullRequestAdded(int $number, string $description, string $user) : Change
    {
        return new Change(
            ChangesSourceMother::pullRequest($number, $user),
            Type::added(),
            $description
        );
    }

    public static function pullRequestChanged(int $number, string $description, string $user) : Change
    {
        return new Change(
            ChangesSourceMother::pullRequest($number, $user),
            Type::changed(),
            $description
        );
    }

    public static function pullRequestFixed(int $number, string $description, string $user) : Change
    {
        return new Change(
            ChangesSourceMother::pullRequest($number, $user),
            Type::fixed(),
            $description
        );
    }

    public static function commitAdded(string $sha, string $description, string $user) : Change
    {
        return new Change(
            ChangesSourceMother::commit($sha, $user),
            Type::added(),
            $description
        );
    }

    public static function commitChanged(string $sha, string $description, string $user) : Change
    {
        return new Change(
            ChangesSourceMother::commit($sha, $user),
            Type::changed(),
            $description
        );
    }

    public static function commitFixed(string $sha, string $description, string $user) : Change
    {
        return new Change(
            ChangesSourceMother::commit($sha, $user),
            Type::fixed(),
            $description
        );
    }
}

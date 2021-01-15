<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother\Changes;

use Aeon\Automation\Changes\ChangesSource;
use Aeon\Calendar\Gregorian\DateTime;

final class ChangesSourceMother
{
    public static function withTitle(string $title) : ChangesSource
    {
        return new ChangesSource(
            ChangesSource::TYPE_PULL_REQUEST,
            '1',
            'https://github.com/aeon-php/automation/pull/1',
            $title,
            'Some long long description that is not valid html',
            DateTime::fromString('2021-01-01'),
            'user',
            'https://github.com/user'
        );
    }

    public static function withDescription(string $description) : ChangesSource
    {
        return new ChangesSource(
            ChangesSource::TYPE_PULL_REQUEST,
            '1',
            'https://github.com/aeon-php/automation/pull/1',
            'Pull Request Title',
            $description,
            DateTime::fromString('2021-01-01'),
            'user',
            'https://github.com/user'
        );
    }

    public static function pullRequest(int $number, string $user) : ChangesSource
    {
        return new ChangesSource(
            ChangesSource::TYPE_PULL_REQUEST,
            (string) $number,
            'https://github.com/aeon-php/automation/pull/1',
            'Pull Request Title',
            'Pull Request description',
            DateTime::fromString('2021-01-01'),
            $user,
            'https://github.com/' . $user
        );
    }

    public static function commit(string $sha, string $user) : ChangesSource
    {
        return new ChangesSource(
            ChangesSource::TYPE_COMMIT,
            $sha,
            'https://github.com/aeon-php/automation/pull/1',
            'Commit Message',
            'Commit Description',
            DateTime::fromString('2021-01-01'),
            $user,
            'https://github.com/' . $user
        );
    }
}

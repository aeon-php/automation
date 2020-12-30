<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother;

use Aeon\Calendar\Gregorian\GregorianCalendar;

final class GitHubResponseMother
{
    public static function tag(string $name, ?string $sha = null) : array
    {
        return [
            'name' => $name,
            'commit' => [
                'sha' => $sha ? $sha : SHAMother::random(),
            ],
        ];
    }

    public static function repository(string $defaultBranch) : array
    {
        return [
            'default_branch' => $defaultBranch,
        ];
    }

    public static function refCommit(string $ref, ?string $sha = null) : array
    {
        return [
            'ref' => 'refs/' . $ref,
            'node_id' => 'MDM6UmVmMjY3NjgzMzQzOnJlZnMvaGVhZHMvMS54',
            'url' => 'https://api.github.com/aeon-php/automation/git/refs/heads/1.x',
            'object' => [
                'sha' => $sha ? $sha : SHAMother::random(),
                'type' => 'commit',
                'url' => 'https://api.github.com/repos/aeon-php/automation/git/commits/' . ($sha ? $sha : SHAMother::random()),
            ],
        ];
    }

    public static function commit(string $message, ?string $sha = null, ?string $date = null) : array
    {
        return [
            'sha' => $sha ? $sha : SHAMother::random(),
            'html_url' => 'http://api.github.com',
            'message' => $message,
            'commit' => [
                'author' => [
                    'email' => 'author@email.com',
                    'date' => $date ? $date : GregorianCalendar::UTC()->now()->toISO8601(),
                ],
                'message' => $message,
            ],
            'author' => [
                'login' => 'user_login',
                'html_url' => 'http//github.com/user_login',
            ],
        ];
    }

    public static function commitWithDate(string $message, string $date) : array
    {
        return [
            'sha' => SHAMother::random(),
            'html_url' => 'http://api.github.com',
            'message' => $message,
            'commit' => [
                'author' => [
                    'email' => 'author@email.com',
                    'date' => $date ? $date : GregorianCalendar::UTC()->now()->toISO8601(),
                ],
                'message' => $message,
            ],
            'author' => [
                'login' => 'user_login',
                'html_url' => 'http//github.com/user_login',
            ],
        ];
    }

    public static function pullRequest(int $number, ?string $title = null, ?string $body = null, ?string $date = null) : array
    {
        return [
            'number' => $number,
            'html_url' => 'http://api.github.com',
            'title' => $title ? $title : 'Pull Request Title',
            'body' => $body ? $body : '## Random Markdown Body',
            'user' => [
                'login' => 'user_login',
                'html_url' => 'http//github.com/user_login',
                'date' => $date ? $date : GregorianCalendar::UTC()->now()->toISO8601(),
            ],
        ];
    }
}

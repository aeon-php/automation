<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother\GitHub;

use Aeon\Calendar\Gregorian\DateTime;
use Aeon\Calendar\Gregorian\GregorianCalendar;
use Webmozart\Assert\Assert;

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

    public static function branch(string $name, ?string $sha = null) : array
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

    public static function pullRequest(int $number, ?string $title = null, ?string $body = null, ?string $date = null, ?string $user = null) : array
    {
        return [
            'number' => $number,
            'html_url' => 'http://api.github.com',
            'title' => $title ? $title : 'Pull Request Title',
            'body' => $body ? $body : '## Random Markdown Body',
            'user' => [
                'login' => $user ? $user : 'user_login',
                'html_url' => $user ? 'http//github.com/' . $user : 'http//github.com/user_login',
            ],
            'merged_at' => $date ? $date : GregorianCalendar::UTC()->now()->toISO8601(),
        ];
    }

    public static function workflow(string $name, ?int $id = null)
    {
        return [
            'id' => $id ? $id : \random_int(100000, 1000000),
            'name' => $name,
            'state' => 'active',
        ];
    }

    public static function workflowRun(?int $id = null)
    {
        return [
            'id' => $id ? $id : \random_int(100000, 1000000),
        ];
    }

    public static function workflowRunJob(string $name, string $status, string $conclusion, ?string $completedAt, ?int $id = null)
    {
        Assert::inArray($status, ['completed', 'queued', 'in_progress']);
        Assert::inArray($conclusion, ['success', 'failure', 'neutral', 'cancelled', 'skipped', 'timed_out', 'action_required', 'stale']);

        return [
            'name' => $name,
            'id' => $id ? $id : \random_int(100000, 1000000),
            'status' => $status, // completed | queued | in_progress
            'conclusion' => $conclusion,
            'completed_at' => $status === 'completed' ? DateTime::fromString($completedAt)->toISO8601() : null,
        ];
    }

    public static function release(int $id, string $name) : array
    {
        return [
            'id' => $id,
            'name' => $name,
        ];
    }
}

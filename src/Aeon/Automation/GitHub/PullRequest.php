<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\ChangesSource;
use Aeon\Automation\Project;
use Aeon\Calendar\Gregorian\DateTime;
use Github\Client;

final class PullRequest implements ChangesSource
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromNumber(Client $client, Project $project, int $number) : self
    {
        return new self($client->pullRequests()->show($project->organization(), $project->name(), $number));
    }

    public function id() : string
    {
        return $this->number();
    }

    public function number() : string
    {
        return (string) $this->data['number'];
    }

    public function url() : string
    {
        return $this->data['html_url'];
    }

    public function title() : string
    {
        return $this->data['title'];
    }

    public function description() : string
    {
        return $this->data['body'] ?? $this->data['title'];
    }

    public function date() : DateTime
    {
        return ($this->data['merged_at'] !== null) ? DateTime::fromString($this->data['merged_at']) : DateTime::fromString($this->data['updated_at']);
    }

    public function user() : string
    {
        return $this->data['user']['login'];
    }

    public function isFrom(string ...$users) : bool
    {
        foreach ($users as $user) {
            if (\strtolower(\trim($this->user())) === \strtolower(\trim($user))) {
                return true;
            }
        }

        return false;
    }

    public function userUrl() : string
    {
        return $this->data['user']['html_url'];
    }

    public function isMerged() : bool
    {
        return $this->data['merged_at'] !== null;
    }

    public function hasMilestone() : bool
    {
        return isset($this->data['milestone']);
    }

    public function equals(ChangesSource $source) : bool
    {
        return $source->id() === $this->id();
    }
}

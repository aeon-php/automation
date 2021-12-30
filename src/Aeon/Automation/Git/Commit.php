<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

use Aeon\Calendar\Gregorian\DateTime;

final class  Commit
{
    private array $data;

    public function __construct(array $data)
    {
        if (!isset($data['commit'])) {
            throw new \InvalidArgumentException('Please get commit from Repository endpoint instead of GitData');
        }

        $this->data = $data;
    }

    public function id() : string
    {
        return $this->sha();
    }

    public function sha() : string
    {
        return $this->data['sha'];
    }

    public function date() : DateTime
    {
        return DateTime::fromString($this->data['commit']['author']['date']);
    }

    public function url() : string
    {
        return $this->data['html_url'];
    }

    public function title() : string
    {
        if (\strstr($this->data['commit']['message'], PHP_EOL)) {
            return \explode(PHP_EOL, $this->data['commit']['message'])[0];
        }

        return $this->data['commit']['message'];
    }

    public function description() : string
    {
        return $this->data['commit']['message'];
    }

    public function user() : string
    {
        if (isset($this->data['author']['login'])) {
            return $this->data['author']['login'];
        }

        return $this->data['commit']['author']['email'];
    }

    public function userUrl() : string
    {
        if (!isset($this->data['author']['html_url'])) {
            return '#';
        }

        return $this->data['author']['html_url'];
    }
}

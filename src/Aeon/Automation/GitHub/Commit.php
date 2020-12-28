<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Automation\Changes;
use Aeon\Automation\ChangesSource;
use Aeon\Calendar\Gregorian\DateTime;

final class Commit implements ChangesSource
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function id() : string
    {
        return $this->data['sha'];
    }

    public function date() : DateTime
    {
        if (isset($this->data['commit'])) {
            return DateTime::fromString($this->data['commit']['author']['date']);
        }

        return DateTime::fromString($this->data['author']['date']);
    }

    public function url() : string
    {
        return $this->data['html_url'];
    }

    public function title() : string
    {
        if (isset($this->data['commit'])) {
            if (\strstr($this->data['commit']['message'], PHP_EOL)) {
                return \explode(PHP_EOL, $this->data['commit']['message'])[0];
            }

            return $this->data['commit']['message'];
        }

        if (\strstr($this->data['message'], PHP_EOL)) {
            return \explode(PHP_EOL, $this->data['message'])[0];
        }

        return $this->data['message'];
    }

    public function user() : string
    {
        if (isset($this->data['commit'])) {
            return $this->data['commit']['author']['email'];
        }

        return $this->data['author']['login'];
    }

    public function userUrl() : string
    {
        if (isset($this->data['commit'])) {
            if (!isset($this->data['commit']['author']['html_url'])) {
                return '#';
            }

            return $this->data['commit']['author']['html_url'];
        }

        if (!isset($this->data['author']['html_url'])) {
            return '#';
        }

        return $this->data['author']['html_url'];
    }

    public function changes() : Changes
    {
        return new Changes(
            $this,
            [],
            [$this->title()],
            [],
            [],
            [],
            []
        );
    }

    public function equals(ChangesSource $source) : bool
    {
        return $source->id() === $this->id();
    }
}

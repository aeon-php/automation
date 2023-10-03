<?php declare(strict_types=1);

namespace Aeon\Automation\Changes;

final class Contributor
{
    private string $name;

    private string $url;

    public function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function url() : string
    {
        return $this->url;
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Git;

final class File
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function name() : string
    {
        return $this->data['name'];
    }

    public function sha() : string
    {
        return $this->data['sha'];
    }

    public function path() : string
    {
        return $this->data['path'];
    }

    public function content() : string
    {
        return \base64_decode($this->data['content'], true);
    }

    /**
     * Double encoding prevents detecting new lines in content from github as a differences from $content.
     */
    public function hasDifferentContent(string $content) : bool
    {
        return \base64_encode(\base64_decode($this->data['content'], true)) !== \base64_encode($content);
    }
}

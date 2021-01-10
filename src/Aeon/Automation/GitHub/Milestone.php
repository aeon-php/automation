<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

final class Milestone
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title() : string
    {
        return $this->data['title'];
    }
}

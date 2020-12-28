<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother;

final class SHAMother
{
    public static function random() : string
    {
        return \sha1(\uniqid());
    }
}

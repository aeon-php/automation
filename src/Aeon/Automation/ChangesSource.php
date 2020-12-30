<?php declare(strict_types=1);

namespace Aeon\Automation;

interface ChangesSource
{
    public function id() : string;

    public function url() : string;

    public function title() : string;

    public function description() : string;

    public function user() : string;

    public function userUrl() : string;

    public function equals(self $source) : bool;
}

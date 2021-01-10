<?php declare(strict_types=1);

namespace Aeon\Automation\Changes;

interface ChangesDetector
{
    public function support(ChangesSource $changesSource) : bool;

    public function detect(ChangesSource $changesSource) : Changes;
}

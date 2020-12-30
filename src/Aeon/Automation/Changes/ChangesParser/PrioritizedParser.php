<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\ChangesParser;

use Aeon\Automation\Changes;
use Aeon\Automation\Changes\ChangesParser;
use Aeon\Automation\ChangesSource;

final class PrioritizedParser implements ChangesParser
{
    /**
     * @var ChangesParser[]
     */
    private array $parsers;

    public function __construct(ChangesParser ...$parsers)
    {
        $this->parsers = $parsers;
    }

    public function support(ChangesSource $changesSource) : bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->support($changesSource)) {
                return true;
            }
        }

        return false;
    }

    public function parse(ChangesSource $changesSource) : Changes
    {
        foreach ($this->parsers as $parser) {
            if ($parser->support($changesSource)) {
                return $parser->parse($changesSource);
            }
        }

        throw new \RuntimeException('There is no parser that supports given changes source');
    }
}

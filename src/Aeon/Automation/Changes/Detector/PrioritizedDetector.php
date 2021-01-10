<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\Detector;

use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesDetector;
use Aeon\Automation\Changes\ChangesSource;

final class PrioritizedDetector implements ChangesDetector
{
    /**
     * @var ChangesDetector[]
     */
    private array $parsers;

    public function __construct(ChangesDetector ...$parsers)
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

    public function detect(ChangesSource $changesSource) : Changes
    {
        foreach ($this->parsers as $parser) {
            if ($parser->support($changesSource)) {
                return $parser->detect($changesSource);
            }
        }

        throw new \RuntimeException('There is no parser that supports given changes source');
    }
}

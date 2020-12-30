<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\ChangesParser;

use Aeon\Automation\Changes;
use Aeon\Automation\Changes\ChangesParser;
use Aeon\Automation\ChangesSource;

final class PrefixParser implements ChangesParser
{
    private const PREFIXES = [
        'added' => ['add', 'added', 'adding'],
        'changed' => ['change', 'changed', 'updated', 'replaced', 'bump'],
        'fixed' => ['fix', 'fixed', 'fixing'],
        'removed' => ['rm', 'removed', 'rem'],
        'deprecated' => ['deprecated', 'dep'],
        'security' => ['security', 'sec'],
    ];

    public function support(ChangesSource $changesSource) : bool
    {
        foreach (self::PREFIXES as $type => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($this->startsWith($prefix . ' ', \strtolower($changesSource->title()))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function parse(ChangesSource $changesSource) : Changes
    {
        foreach (self::PREFIXES as $type => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($this->startsWith($prefix . ' ', \strtolower($changesSource->title()))) {
                    return new Changes(
                        $changesSource,
                        new Changes\Change(
                            Changes\Type::$type(),
                            \substr($changesSource->title(), \strlen($prefix) + 1)
                        )
                    );
                }
            }
        }

        throw new \RuntimeException("Can't get changes from source title prefix");
    }

    private function startsWith(string $needle, string $haystack) : bool
    {
        return \substr($haystack, 0, \strlen($needle)) === $needle;
    }
}

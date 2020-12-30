<?php

declare(strict_types=1);

namespace Aeon\Automation\ChangeLog;

use Aeon\Automation\ChangeLog;
use Aeon\Automation\Changes\Change;
use Aeon\Automation\ChangesSource;
use Aeon\Automation\GitHub\Commit;

final class MarkdownFormatter implements Formatter
{
    public function format(ChangeLog $changeLog) : string
    {
        $output = \sprintf("## %s - %s\n", $changeLog->release(), $changeLog->day()->toString());

        $added = '';
        $changed = '';
        $fixed = '';
        $deprecated = '';
        $removed = '';
        $security = '';

        foreach ($changeLog->changes() as $changes) {
            foreach ($changes->added() as $entry) {
                $added .= $this->formatEntry($changes->source(), $entry);
            }

            foreach ($changes->changed() as $entry) {
                $changed .= $this->formatEntry($changes->source(), $entry);
            }

            foreach ($changes->fixed() as $entry) {
                $fixed .= $this->formatEntry($changes->source(), $entry);
            }

            foreach ($changes->deprecated() as $entry) {
                $deprecated .= $this->formatEntry($changes->source(), $entry);
            }

            foreach ($changes->removed() as $entry) {
                $removed .= $this->formatEntry($changes->source(), $entry);
            }

            foreach ($changes->security() as $entry) {
                $security .= $this->formatEntry($changes->source(), $entry);
            }
        }

        if (\strlen($added)) {
            $output .= "### Added\n";
            $output .= $added . "\n";
        }

        if (\strlen($changed)) {
            $output .= "### Changed\n";
            $output .= $changed . "\n";
        }

        if (\strlen($fixed)) {
            $output .= "### Fixed\n";
            $output .= $fixed . "\n";
        }

        if (\strlen($removed)) {
            $output .= "### Removed\n";
            $output .= $removed . "\n";
        }

        if (\strlen($deprecated)) {
            $output .= "### Deprecated\n";
            $output .= $deprecated . "\n";
        }

        if (\strlen($security)) {
            $output .= "### Security\n";
            $output .= $security . "\n";
        }

        return $output;
    }

    private function formatEntry(ChangesSource $source, Change $change) : string
    {
        return \sprintf(
            " - [%s](%s) - **%s** - [@%s](%s)\n",
            ($source instanceof Commit) ? \substr($source->id(), 0, 6) : ('#' . $source->id()),
            $source->url(),
            $change->description(),
            $source->user(),
            $source->userUrl()
        );
    }
}

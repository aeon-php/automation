<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Configuration;
use Aeon\Automation\Git\Git;
use Aeon\Automation\Release;
use Aeon\Calendar\Gregorian\Calendar;

final class ReleaseService
{
    private Configuration $configuration;

    private Options $options;

    private Calendar $calendar;

    private Git $git;

    public function __construct(Configuration $configuration, Options $changelogOptions, Calendar $calendar, Git $github)
    {
        $this->configuration = $configuration;
        $this->options = $changelogOptions;
        $this->git = $github;
        $this->calendar = $calendar;
    }

    public function fetch() : History
    {
        $scopeDetector = new ScopeDetector($this->git, $this->options->isTagOnlyStable());

        $scope = $scopeDetector->default(
            $scopeDetector->fromTags($this->options->tagStart(), $this->options->tagEnd())
                ->override($scopeDetector->fromCommitSHA($this->options->commitStartSHA(), $this->options->commitEndSHA())),
            $this->options->branch()
        );

        if ($this->options->compareReverse() && $scope->isFull()) {
            $scope = $scope->reverse();
        }

        return new History($this->git, $scope, $this->options->changedAfter(), $this->options->changedBefore());
    }

    public function analyze(History $history, callable $onProgress) : Release
    {
        $transformer = new HistoryTransformer(
            $this->git,
            $this->options
        );

        $changeSources = $transformer->transform($history, $onProgress);

        $release = new Release($this->options->releaseName(), $history->scope()->commitStart() ? $history->scope()->commitStart()->date()->day() : $this->calendar->currentDay());

        $changesDetector = $this->configuration->changesDetector();

        foreach ($changeSources as $source) {
            $release->add($changesDetector->detect($source));
        }

        return $release;
    }
}

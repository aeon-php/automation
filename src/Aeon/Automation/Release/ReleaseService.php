<?php

declare(strict_types=1);

namespace Aeon\Automation\Release;

use Aeon\Automation\Configuration;
use Aeon\Automation\GitHub\GitHub;
use Aeon\Automation\Project;
use Aeon\Automation\Release;
use Aeon\Calendar\Gregorian\Calendar;

final class ReleaseService
{
    private Configuration $configuration;

    private Options $options;

    private Calendar $calendar;

    private GitHub $github;

    private Project $project;

    public function __construct(Configuration $configuration, Options $changelogOptions, Calendar $calendar, GitHub $github, Project $project)
    {
        $this->configuration = $configuration;
        $this->options = $changelogOptions;
        $this->github = $github;
        $this->project = $project;
        $this->calendar = $calendar;
    }

    public function fetch() : History
    {
        $scopeDetector = new ScopeDetector($this->github, $this->project);

        $scope = $scopeDetector->default(
            $scopeDetector->fromTags($this->options->tagStart(), $this->options->tagEnd())
                ->override($scopeDetector->fromCommitSHA($this->options->commitStartSHA(), $this->options->commitEndSHA()))
        );

        if ($this->options->compareReverse() && $scope->isFull()) {
            $scope = $scope->reverse();
        }

        return new History($this->github, $this->project, $scope, $this->options->changedAfter(), $this->options->changedBefore());
    }

    public function analyze(History $history, callable $onProgress) : Release
    {
        $transformer = new HistoryTransformer(
            $this->github,
            $this->project,
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

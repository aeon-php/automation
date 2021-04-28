<?php

declare(strict_types=1);

namespace Aeon\Automation\GitHub;

use Aeon\Calendar\TimeUnit;

final class WorkflowTiming
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function isEmpty() : bool
    {
        foreach ($this->data['billable'] as $billable) {
            foreach ($billable as $operatingSystem => $value) {
                return false;
            }
        }

        return true;
    }

    public function operatingSystems() : array
    {
        $systems = [];

        foreach ($this->data['billable'] as $billable) {
            foreach ($billable as $operatingSystem => $value) {
                $systems[] = $operatingSystem;
            }
        }

        return $systems;
    }

    public function ubuntuTime() : ?TimeUnit
    {
        foreach ($this->data['billable'] as $operatingSystem => $billable) {
            if ($operatingSystem === 'UBUNTU') {
                return TimeUnit::milliseconds($billable['total_ms']);
            }
        }

        return null;
    }

    public function macosTime() : ?TimeUnit
    {
        foreach ($this->data['billable'] as $billable) {
            foreach ($billable as $operatingSystem => $value) {
                if ($operatingSystem === 'MACOS') {
                    return TimeUnit::milliseconds($value['total_ms']);
                }
            }
        }

        return null;
    }

    public function windowsTime() : ?TimeUnit
    {
        foreach ($this->data['billable'] as $billable) {
            foreach ($billable as $operatingSystem => $value) {
                if ($operatingSystem === 'WINDOWS') {
                    return TimeUnit::milliseconds($value['total_ms']);
                }
            }
        }

        return null;
    }
}
